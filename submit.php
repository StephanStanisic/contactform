<?php

include_once("class.ContactForm.php");

$ContactForm = new ContactForm(false);
$ContactForm->init();
$fail = $ContactForm->submission($_POST);

if(!$fail) {
    echo <<<HTML
<script>alert("Form successfully submited."); //history.go(-1);</script>
<noscript>
    Form successfully submited.<br />
    <a href=".">Go back.</a>
</noscript>
HTML;
} else {
    echo <<<HTML
<script>alert("Something went wrong. Please try again.\n\n$fail"); //history.go(-1);</script>
<noscript>
    Something went wrong. Please try again.<br /><br />
    $fail
    <a href=".">Go home.</a>
</noscript>
HTML;
}

?>
