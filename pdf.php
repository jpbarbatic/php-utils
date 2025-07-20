<?php

require(BASE_DIR . '/lib/fpdf/fpdf.php');

class ListadoPDF extends FPDF
{
    public $fields;
    public $title;
    public $num_registros;

    function __construct($title, $fields)
    {
        parent::__construct();
        $this->fields = $fields;
        foreach($this->fields as $key=>$field)
        {
            if(!isset($field['width'])){
                $this->fields[$key]['width']=20;
            }

            if(!isset($field['display'])){
                $this->fields[$key]['display']=ucfirst($key);
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
        $this->Cell(0, 10, $this->title, 0, 0, 'C');

        $this->SetFont('Arial', '', 10);
        // Título
        $this->Ln(1);
        $this->Cell(0, 18, $this->utf('Nº registros: ').$this->num_registros, 0, 0, 'C');

        // Salto de línea
        $this->Ln(15);

        // Colores, ancho de línea y fuente en negrita
        $this->SetFillColor(100, 100, 100);
        $this->SetTextColor(255);
        //$this->SetDrawColor(128,0,0);
        //$this->SetLineWidth(.3);

        $this->SetFont('', 'B', 8);

        foreach ($this->fields as $field) {
            $this->Cell($field['width'] * ($this->GetPageWidth() - 20) / 100, 7, $this->utf($field['display']), 1, 0, 'C', true);
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
        $this->AddPage();
        // Restauración de colores y fuentes
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 8);

        // Datos
        $fill = false;
        foreach ($data as $row) {
            foreach ($this->fields as $key=>$field) {
                $text = $this->filterField($row[$key], isset($field['type']) ? $field['type'] : null);
                $this->Cell($field['width'] * ($this->GetPageWidth() - 20) / 100, 6, $text, 'LR', 0, isset($field['align'])?$field['align']:'C', $fill);
            }
            $this->Ln();
            $fill = !$fill;
        }
        $this->Output('I', 'listado.pdf');
    }

    public function filterField($str, $type = null)
    {
        if ($type == null)
            return $this->utf(ucfirst($str));

        if ($type == 'money')
            return $this->utf(number_format(floatval($str), 2, ',') . " €");
    }
}

function generar_listado($titulo, $campos, $datos)
{
    $pdf = new ListadoPDF($titulo, $campos);
    $pdf->render($datos);
}
