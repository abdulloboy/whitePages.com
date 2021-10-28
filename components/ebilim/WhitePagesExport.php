<?php

namespace app\components\ebilim;

use yii\base\BaseObject;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Exception;

class WhitePagesExport extends BaseObject
{

    public function __construct($param1 = NULL, $param2 = NULL, $config = [])
    {
        parent::__construct($config);
    }
    
    public function init()
    {
        parent::init();
    }

    public function generateReport($sOutputFile = NULL, $aRequestResult = NULL,$aCsv)
    {
        $spreadsheet = NULL;
        $sheet = NULL;

        if(isset($aRequestResult) && 
            is_array($aRequestResult) && count($aRequestResult))
        {
            try
            {
                $spreadsheet = new Spreadsheet();

                // Ориентация и размер бумаги
                $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

                $sheet = $spreadsheet->getActiveSheet();

                $iLineNumber = 1;

                $sheet->setCellValue("A{$iLineNumber}", '#');
                $sheet->setCellValue("B{$iLineNumber}", 'Full Name');
                $sheet->setCellValue("C{$iLineNumber}", 'Age');
                $sheet->setCellValue("D{$iLineNumber}", 'Birth Date');
                $sheet->setCellValue("E{$iLineNumber}", 'Address');
                $sheet->setCellValue("F{$iLineNumber}", 'Address Past');
                $sheet->setCellValue("G{$iLineNumber}", 'Address Past');
                $sheet->setCellValue("H{$iLineNumber}", 'URL');

                $sheet->getStyle("A{$iLineNumber}:N{$iLineNumber}")->getFont()->setBold(true);

                $iLineNumber = 2;

                for($i1=0;$i1<count($aRequestResult);$i1++){
                    $iItemNumber = 1;
                    $sheet->setCellValue("E{$iLineNumber}",implode(' ',$aCsv[$i1]));
                    $sheet->getStyle("A{$iLineNumber}:N{$iLineNumber}")->getFont()->setBold(true);
                    $iLineNumber++;

                    foreach($aRequestResult[$i1] as $iKey => $aItems)
                    {
                        $iLastLineNumber = $iLineNumber;

                        $sheet->setCellValue("A{$iLineNumber}", $iItemNumber);
                        $sheet->setCellValue("B{$iLineNumber}", $aItems['fullName']);
                        $sheet->setCellValue("C{$iLineNumber}", $aItems['age']);
                        $sheet->setCellValue("D{$iLineNumber}", $aItems['birthDate']);
                        if(count($aItems['address']))
                            $sheet->setCellValue("E{$iLineNumber}", $aItems['address'][0]);
                        if(count($aItems['address'])>1)
                            $sheet->setCellValue("F{$iLineNumber}", $aItems['address'][1]);
                        if(count($aItems['address'])>2)
                            $sheet->setCellValue("G{$iLineNumber}", $aItems['address'][2]);
                        $sheet->setCellValue("H{$iLineNumber}", $aItems['URL']);

                        $iLineNumber++;
                        $iItemNumber++; 
                    }
                }

                $sheet->getColumnDimension('A')->setAutoSize(true);
                $sheet->getColumnDimension('B')->setAutoSize(true);
                $sheet->getColumnDimension('C')->setAutoSize(true);
                $sheet->getColumnDimension('D')->setAutoSize(true);
                $sheet->getColumnDimension('E')->setAutoSize(true);
                $sheet->getColumnDimension('F')->setAutoSize(true);
                $sheet->getColumnDimension('G')->setAutoSize(true);
                $sheet->getColumnDimension('H')->setAutoSize(true);
                
                $writer = new Xlsx($spreadsheet);

                try
                {
                    $writer->save($sOutputFile);
                }
                catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e)
                {
                    print_r($e->message);
                }
            }
            catch (Exception $e)
            {
                print_r($e->message);
            }

        }

        return true;

    }
}

?>