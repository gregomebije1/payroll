<?php
/**
 * PHPExcel
 *
 * Copyright (c) 2006 - 2015 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2015 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    ##VERSION##, ##DATE##
 */

/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
date_default_timezone_set('Africa/Lagos');

if (PHP_SAPI == 'cli')
	die('This example should only be run from a Web Browser');

/** Include PHPExcel */
//require_once dirname(__FILE__) . '/../Classes/PHPExcel.php';
require_once dirname(__FILE__) . '/../vendor/phpoffice/phpexcel/Classes/PHPExcel.php';
require_once dirname(__FILE__) . '/../classes/Payroll.php';

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set document properties
$objPHPExcel->getProperties()->setCreator("HyperAccounting")
							 ->setLastModifiedBy("HyperAccounting")
							 ->setTitle("Office 2007 XLSX payroll Document")
							 ->setSubject("Office 2007 XLSX payroll Document")
							 ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
							 ->setKeywords("office 2007 openxml php")
							 ->setCategory("Payroll document");


// Add some data
$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'Sender')
            ->setCellValue('B1', 'Name')
            ->setCellValue('C1', 'Othername')
            ->setCellValue('D1', 'Email')
			->setCellValue('E1', 'Phone')
			->setCellValue('F1', 'Bank')
			->setCellValue('G1', 'Sort code')
			->setCellValue('H1', 'Account')
			->setCellValue('I1', 'Amount');

/*Get data from database and add to excell sheet*/
/*
$db = getConnection();
$sql = "select firstname, middlename, lastname, mobile_telephone_number, bank_account_number, 
        b.name as 'bank' from employee e join bank b on e.bank_id = b.id";
$result = $db->query($sql);
//$data = $stmt->fetchAll(PDO::FETCH_OBJ);
$row_count = 2;
while($row = $result->fetch()) {
*/
$payroll = new Payroll;
$rows = $payroll->generate_payment_schedule($_REQUEST['date'], $_REQUEST['branch_id'], $_REQUEST['location_id'], $_REQUEST['bank_id']);

$row_count = 2;
foreach($rows as $row) {
	$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue("A{$row_count}", 'Sender')
            ->setCellValue("B{$row_count}", "{$row['firstname']} {$row['middlename']} {$row['lastname']}")
            ->setCellValue("C{$row_count}", 'Othername')
            ->setCellValue("D{$row_count}", 'Email')
			->setCellValue("E{$row_count}", "{$row['mobile_telephone_number']}")
			->setCellValue("F{$row_count}", 'Bank')
			->setCellValue("G{$row_count}", 'Sort code')
			->setCellValue("H{$row_count}", "{$row['bank_account_number']}")
			->setCellValue("I{$row_count}", $payroll->get_net_pay($row['basic_salary'], $row['payroll_id']));
			
	$row_count += 1;
}
	
// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle('Payroll schedule');


// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);


// Redirect output to a client’s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="payorll.xls"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');
exit;


