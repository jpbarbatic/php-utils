<?php

require(BASE_DIR . '/lib/fpdf/fpdf.php');
require(BASE_DIR . '/lib/tcpdf/tcpdf.php');

class ListadoPDF extends FPDF
{
    public $fields;
    public $title;
    public $num_registros;
    public $alto_fila;

    function __construct($title, $fields, $opciones)
    {
        parent::__construct();
        $this->fields = $fields;
        if ($opciones) {
            if (isset($opciones['alto_fila'])) {
                $this->alto_fila = $opciones['alto_fila'];
            }
        }

        foreach ($this->fields as $key => $field) {
            if (!isset($field['width'])) {
                $this->fields[$key]['width'] = 20;
            }

            if (!isset($field['display'])) {
                $this->fields[$key]['display'] = ucfirst($key);
            }
        }
        $this->title = $title;
    }
    // Cabecera de página
    function Header()
    {
        // Logo
        if (defined('PDF_LOGO')) {
            $this->Image(PDF_LOGO, 10, 10, 20);
        }
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);

        // Título
        $this->Cell(0, 10, $this->utf($this->title), 0, 0, 'C');

        $this->SetFont('Arial', '', 10);
        // Título
        $this->Ln(1);
        $this->Cell(0, 18, $this->utf('Nº registros: ') . number_format($this->num_registros, 0, ',', '.'), 0, 0, 'C');

        // Salto de línea
        $this->Ln(15);

        // Colores, ancho de línea y fuente en negrita
        $this->SetFillColor(100, 100, 100);
        $this->SetTextColor(255);
        //$this->SetDrawColor(128,0,0);
        //$this->SetLineWidth(.3);

        $this->SetFont('', 'B', 8);

        foreach ($this->fields as $field) {
            $ancho = $field['width'] * ($this->GetPageWidth() - 20) / 100;
            $this->Cell($ancho, 7, $this->utf($field['titulo']), 1, 0, 'C', true);
        }

        $this->Ln();
    }

    /**
     * utf
     * Esta función es necesaria para convertir de UTF al formato regional. Si no se utiliza,
     * da problema con tildes y letras especiales
     * @param  mixed $texto
     * @return void
     */
    function utf($texto)
    {
        return iconv('utf-8', 'ISO-8859-1//IGNORE', $texto);
    }

    // Pie de página
    function Footer()
    {
        // Línea de cierre
        $this->Cell(0, 0, '', 'T');

        // Posición: a 1,5 cm del final
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', '', 8);
        // Número de página
        $this->Cell(0, 10, $this->utf('Página ') . $this->PageNo() . ' - Fecha: ' . date('d/m/Y'), 0, 0, 'C');
    }

    /**
     * render
     *
     * @param  mixed $data
     * @return void
     */
    public function render($data)
    {
        $this->num_registros = count($data);
        $this->AddPage('L');
        // Restauración de colores y fuentes
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 8);
        $this->SetMargins(10, 10, 10, 10);

        // Datos
        $fill = false;
        foreach ($data as $row) {
            foreach ($this->fields as $key => $field) {
                $ancho = ($field['width'] * ($this->GetPageWidth() - 20)) / 100;
                if (isset($field['func'])) {
                    $func = $field['func'];
                    if ($field['type'] == 'image') {
                        $this->Cell($ancho, $this->alto_fila, $this->Image($func($row), $this->GetX() + 2, $this->GetY() + 2, $ancho - 4, 30 - 4, null), 1, 0, isset($field['align']) ? $field['align'] : 'C');
                        //$this->Image($func($row), $this->GetX()+2, $this->GetY()+2, $ancho-4, 30-4);
                    }
                } else {
                    //echo $field['campo'];
                    $texto = $this->valorCelda($row[$field['campo']], $field);
                    $this->Cell($ancho, $this->alto_fila, $texto, 1, 0, isset($field['align']) ? $field['align'] : 'C');
                }
            }
            $this->Ln();
            $fill = !$fill;
        }
        $this->Output('I', 'listado.pdf');
    }

    public function valorCelda($str, $field)
    {
        if (isset($field['type']) and $field['type'] == 'money')
            return $this->utf(number_format(floatval($str), 2, ',') . " €");

        if (isset($field['valores'])) {
            //echo "Tiene valores".PHP_EOL;
            //print_r($field['valores']); echo intval($str); exit;
            $str = $field['valores'][intval($str)];
            return $this->utf(ucfirst($str));
        }

        if ($str == null)
            return '';
        return $this->utf(ucfirst($str));
    }
}

function generar_listado($titulo, $campos, $datos, $opciones = null)
{
    $pdf = new ListadoPDF($titulo, $campos, $opciones);
    $pdf->render($datos);
}


function crear_html_plantilla($plantilla, $datos)
{
    extract($datos);
    ob_start();
    include $plantilla;
    $html = ob_get_contents();
    ob_end_clean();
    //echo $html; exit;
    return $html;
}

function generar_pdf($plantilla, $titulo, $datos = [])
{
    // create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // set document information
    $pdf->setCreator(PDF_CREATOR);
    $pdf->setAuthor('Colonias Casariche');
    $pdf->setTitle($titulo);
    $pdf->setSubject('TCPDF Tutorial');
    $pdf->setKeywords('TCPDF, PDF, example, test, guide');

    //$pdf->setPrintHeader(false);
    //$pdf->setPrintFooter(false);

    // set default header data
    $pdf->setHeaderData('logo.png', 18, $titulo, 'Colonias Casariche', array(0, 0, 0), array(255, 255, 255));
    $pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));

    // set header and footer fonts
    $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // set default monospaced font
    $pdf->setDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // set margins
    $pdf->setMargins(PDF_MARGIN_LEFT, 20, PDF_MARGIN_RIGHT, true);
    $pdf->setHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->setFooterMargin(PDF_MARGIN_FOOTER);

    // set auto page breaks
    $pdf->setAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);


    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // set default font subsetting mode
    $pdf->setFontSubsetting(true);

    // Set font
    // dejavusans is a UTF-8 Unicode font, if you only need to
    // print standard ASCII chars, you can use core fonts like
    // helvetica or times to reduce file size.
    $pdf->setFont('dejavusans', '', 14, '', true);

    // Add a page
    // This method has several options, check the source code documentation for more information.
    $pdf->AddPage();

    // set text shadow effect
    //$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));

    // Set some content to print
    $html = crear_html_plantilla($plantilla, $datos);

    // Print text using writeHTMLCell()
    $pdf->writeHTML($html, true, false, false, false, '');

    $pdf->Output($titulo . '.pdf', 'I');
}
