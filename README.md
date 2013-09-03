usetiful
========

What Usetiful?
Usetiful is a beatiful useful class generator for Php / Mysql.
It allows you to create the classes for Create, Read and Update Mysql records.

Why Usetiful?
The main purpose is to avoid writing many lines of code, saving your time.

How Usetiful?
it's really easy to use. Download the zip file, extract it to the root where you want. 
The Steps are the following:

- create a simple Php file (see the example below);
- include usetiful class (file you have downloaded);
- istantiate the object passing all the required variables in the constructor;
- enjoy your created classes! ^_^


Example:

<?php
include_once 'usetiful.php';
$server = "127.0.0.1"; //where your db is...
$username = "root"; //db username
$password = ""; //db password
$schema = ""; //db name / schema
$project_name = "folder1"; // where you would like to create your project folder 
$mp = new Usetiful($server, $username, $password, $schema, $project_name, $table = false);
?>

IMPORTANT: For the correct execution of Usetiful you must have the mysql priviliges to select information_schema and possibly you should have administrator privileges of the webserver (also local)


Usetiful creates a folder containing some files (the first 3 are the fundamentals layers):

- Class.Actions.php
- Class.Business.php
- Class.Persistence.php
- Class.DaoUtils.php
- Class.Email.php
- Class.Html.php
- Class.Trigger.php
- Class.Validation.php
- Constants.php

It also creates one folder for each mysql table found containing a sample index.php page that shows you how easy it is now to create a select statement (just done).

Usetiful - files created.
Class.Actions.php: This is the entry point. There are the methods to perform CRUD any calls or other. By instantiating the Action object you'll get what you need.

Class.Business.php: Classes accompanied by their respective getters and setters methods already written. Beautiful is not it?

Class.DaoUtils.php: Connection to db. At the moment is still used the connection method mysql_connect. Will be replaced in the shortest possible time.

Class.Email.php: Send emails based on standard class Mail php.

Class.Html.php: A very small html framework that might be useful to build pages quickly.

Class.Persistence.php: The class that contains the heart of the application. Contains the CRUD (CRU) and some methods for queries.

Class.Trigger.php: A support class that could serve for handling exceptions

Class.Validation.php: A class designed for server-side validation of the fields. Not fully implemented yet.

Constants.php: A small class of constants. 



