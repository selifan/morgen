<?php
/**
* @name morgen-demo.php
* Demonstrating functionality of morgen.php
* @author Alexander Selifonov
*/


include_once('../src/morgen.php');

$generator = new \Morgen\IconGenerator();
$generator->setSvgConvertor('D:/app/inkscape/inkscape.exe -z {from} -e {to}');

$options = array(
  'project' => 'project-greatApp.xml',
  'forced'  => true
);

$generator->createIconsFromImages($options);
