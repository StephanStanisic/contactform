<?php

global $Wcms;

include_once("class.ContactForm.php");

$ContactForm = new ContactForm(true);
$ContactForm->init();
$ContactForm->replace();

?>
