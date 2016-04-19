# atk-builder

## Code generator tool for the ATK Framework

atk-builder is a code generator tool for the ATK Framework (See https://github.com/Sintattica/atk ).

The idea behind atk-builder is to write the minimun possible to have a fully functional
working ATK application.
An ATK application is built around modules that have nodes that have attributes, you normally make a folder inside the modules folders and populate that folder with classes derived from the **Node.class**, tha node will correspond to a table inside your database and inside your node you declare the table and add an attribute for every column, the process, albeit simple is time consuming.
atk-builder let's you avoid a lot of work, atk-builder let's you declare a simple text file called by default **DefFile** wich contains definitions about the modules and nodes that comprises the ATK application.
After parsing this **DefFile** atk-builder will:

- Create or Drop the required tables.
- Add or Drop the required columns to the table.
- Write or Re-write the required code for the Module class and / or the node/s class/es.

A simple **DefFile** look like this:

```
appnme:myapp
db:myappdb:root:pass

module:payroll
	node:employees
		name
		date_of_birth
		salary_ammount
		notes
```

It defines the application name (myapp) the database name (myappdb) the user and password of the database (root ans pass) it defines a module (payroll) wich contains a node (employees) wich have several attributes (name, date_of_birth, salary_ammount, notes)

Running atk-builder without any arguments looks for a file called **DefFile** in the current directory, running the tool with the previous definition will:

- Create the database if it not exists.
- Create the payroll_employee tables if it not exists.
- If the table exists it will add or drop the corresponding columns in order to adjust the table to the definition.
- Will create the Module folder.
- Will create the Module class.
- Will create the Employees class with the correct attributes for each columns.

The attributes/columns types are infered using their names so date_of_birth will result in a column called data_of_birth with DATE type and an attribut of type DateAttribute.
Not only the type is infered on the name, the flags are infered too, the name column will have a Attribute::AF_SEARCH flag because searching for a name is an obvious requirement it will have an Attribute::AF_OBLIGATORY as well, because obviously a name is allway required.
atk-builder will try to infer as much as possible on your **DefFile** but you can fine tune the code generation i.e:

```
appnme:myapp
db:myappdb:root:pass

module:payroll
	node:employees
		name:Employe Name:Attribute:AF_OBLIGATORY, 30
		date_of_birth
		salary_ammount
		notes
```

The modified line :

```
name:Employe Name:Attribute:AF_OBLIGATORY, 30
```

Means that the name column label in forms will be **Employee Name** it will be rendered as a simple attribute with a width of 30 chars and it will have the AF_OBLIGATORY flag set.

The full syntax of the **DefFile** is as follows:

```
appnme:myapp
db:myappdb:root:pass

module:payroll
	node:employees:[label]:[actions]:[node_flags]:[show_in_menu]
		name:[label]:[attribute_type]:[flags]:[tab]
		
		
```

The items between square brackets are optional, if ommited, proper values will be infered for them based on context.