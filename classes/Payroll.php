<?php 
require_once("../payroll.inc");
require_once("../config_profile_security.inc");

  
class Payroll {
	public $db = NULL;
	function __construct() {
		$this->db = $this->getConnection();
	}
	function generate_payment_schedule($date, $branch_id, $location_id, $bank_id) {
		$branch_sql = ($branch_id === "0") ? "branch_id != 0" : "branch_id = '{$branch_id}'";
		$location_sql = ($location_id === "0") ? "location_id != 0" : "location_id = '{$location_id}'";
		$bank_sql = ($bank_id === "0") ? "bank_id != 0" : "bank_id = '{$bank_id}'";
		
		$sql = "select e.firstname, middlename, lastname, mobile_telephone_number, bank_account_number,
			p.basic_salary, p.id as 'payroll_id' from employee e join payroll p on e.id = p.employee_id
			where {$branch_sql} and {$location_sql} and {$bank_sql}
			and payroll_date = '{$date}' and status='Enable' order by e.id";
			
		$result = $this->db->query($sql);

		$employees = [];
		while($row = $result->fetch()) {
			$employees[] = $row;
		}
		return $employees;
	}
	
	function get_net_pay($basic_salary, $payroll_id) {
		$net_pay = $basic_salary;
		
		$sql="select d.type, d.name, pdi.amount from payroll_di pdi 
			join di d on pdi.di = d.id where payroll_id = '{$payroll_id}'";
		$result = $this->db->query($sql);
		while($row = $result->fetch()) {
		
			if ($row['type'] === 'Deductions')
				$net_pay -= $row['amount'];
			else 
				$net_pay += $row['amount'];
		}
		return $net_pay;
	}
	//Connection Database
	function getConnection() {
		global $dbserver, $dbusername, $dbpassword, $database;
		$dbh = new PDO("mysql:host=$dbserver;dbname=$database", $dbusername, $dbpassword);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbh;
	}
}
?>