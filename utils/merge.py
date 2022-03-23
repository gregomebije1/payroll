from __future__ import print_function
from datetime import date, datetime, timedelta
import mysql.connector
from config import *
from config2 import *
from config3 import *

cnx = mysql.connector.connect(**config)
cursor = cnx.cursor()


cnx2 = mysql.connector.connect(**config2)
cursor2 = cnx2.cursor()

cnx3 = mysql.connector.connect(**config3)
cursor3 = cnx3.cursor()

query2 = ("SELECT * from payroll")
cursor2.execute(query2)

for (id, employee_id, payroll_date, basic_salary) in cursor2:
  print("{} being processed".format(id))

  add_payroll = ("INSERT INTO payroll "
               "(employee_id, payroll_date, basic_salary) "
               "VALUES (%s, %s, %s)")

  data_payroll = (employee_id, payroll_date, basic_salary)

  cursor.execute(add_payroll, data_payroll)
  payroll_idx = cursor.lastrowid

  print(id)
  query3 = "select id, payroll_id, di, amount from payroll_di where payroll_id =" + str(id)
  cursor3.execute(query3)
  for (id, payroll_id, di, amount) in cursor3:

    add_payroll_di = ("INSERT INTO payroll_di"
              "(payroll_id, di, amount) "
              "VALUES (%s, %s, %s)")
    data_payroll_di = (payroll_idx, di, amount)
    cursor.execute(add_payroll_di, data_payroll_di)

# Make sure data is committed to the database
#cnx.commit()

#cursor.close()
#cnx.close()
