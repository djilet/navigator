<?php
require_once(dirname(__FILE__) . "/../include/init.php");
es_include("mpdf60/mpdf.php");

function createPDF($from, $to){
	$src_dir = PROJECT_DIR.'var/pdf/';
	$html = '';
	for($i=$from;$i<=$to;$i++){
		$file = $src_dir.'/output'.$i.'.png';
		$filename = basename($file);
		$html.= '<br><br><br><br><h1 align=center>Билет №'.$i.'</h1><p align=center><img style="width:80%" src="'.$file.'"></p>';
		if($i<$to) {
		    $html.= '<pagebreak />';
		}
	}
	$pdf = new mPDF('utf-8', 'A6', '8', '', 10, 10, 7, 7, 10, 10);
	$pdf->WriteHTML($html);
	$pdfName = 'Ticket_'.$from.'_'.$to.'.pdf';
	$pdf->Output($src_dir.'/output/'.$pdfName);
}
//createPDF(100000001, 100001000);

function createAllPDF(){
    for($i=60; $i<110; $i++){
        $from = 100000000 + ($i * 1000) + 1;
        $to = 100000000 + (($i+1) * 1000);
        createPDF($from, $to);
    }
}
createAllPDF();
