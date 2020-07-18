  # Historical Model
  
  This package created for helping you to create a historical model/data for any model/data you want, so one of the solutions of creating a historical data os by creating a history table for the basic table.
  
  so if we have a table called employees with this columns:
  - id
  - name
  - salary
  - address
  - created_at
  - updated_at
  
  and we want to make save our data in another place in case if the employee changes his address or his salary for example,
  like if the salary of an employee is 500$ then he updated the salary to be 1000$ then 1500$ so we want to save all of these data to have a historical data to know when the employee was 1000$ or something else.
  
  Our history table schema will be:
  - id - primary key. Detail/historical id
  - master_id - for example in this case called 'employee_id', this is the FK to the Master record
  - start_datetime - timestamp indicating the start of that database row
  - end_datetime - timestamp indicating the end of that database row
  - status control - single char column indicated status of the row. 'c' indicates current, NULL or 'a' would be historical/archived. We only use this because we can't index on END_DATETIME being NULL
  - created_by_id - stores the ID of the account that caused the row to be created
  - the_columns_you_choose_to_log - stores the actual data  
     
 you can create the historical model/data just by a very easy command  
  - `php artisan make:history-model`

then you will be asked what model you want to make it historical,

![Screenshot](https://i.ibb.co/3MTxsjF/Screenshot-from-2020-07-19-01-32-50.png)

and the package will automatically get your columns and ask you column column with y/n if you to log it, and hola you will get your model and your migration file
ready to migrate.

![Screenshot](https://i.ibb.co/PwhQT2r/Screenshot-from-2020-07-19-01-33-25.png)

   
  * [Important](#imp)
  
        There are two an improtant things you should do after runing the above command:
        - first you should remove the `$table->timestamps()` fom the migration file
        - you should use the `Geeky\Historical\Concerns\Historical` trait file in your base model 
