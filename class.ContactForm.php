<?php

/*  This plugin has been written at 1 AM. If you are reading this in hopes for
 *  updating, iproving or modifying this code; good luck.
 *
 *  Otherwise you'll want to use config.json. It's self-documented.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ContactForm {

    private $Wcms;

    private $config;

    public function __construct($load) {
        if($load) {
            global $Wcms;
            $this->Wcms =& $Wcms;
        }
	}

    public function init() : void {
    }

    private function fetchConfig() : void {
        $this->config = $this->json_clean_decode(file_get_contents(__DIR__ . "/config.json"));
        if($this->config == null) die("<b>Parse error:</b> Invalid JSON syntax in ".__DIR__."/config.json. Make sure you copy config.json.sample to config.json and set it up.");
    }

    public function replace() : void {
        $this->attach();
    }

    public function attach() : void {
        $this->Wcms->addListener('page', [$this, "pageListener"]);
    }

    public function pageListener(array $args) : array {
        if($args[1] != "content") return $args;
        if($this->Wcms->loggedIn) return $args;

        // Only fetch config when needed
        $this->fetchConfig();

        // Check if current page content contains the shortcode.
        if(strpos($args[0], $this->config->shortcode) !== false) {
            $form = $this->assembleForm();

            $args[0] = str_replace($this->config->shortcode, $form, $args[0]);
        }

        return $args;
    }

    private function assembleForm() : string {
        // Using regular strings here so you dont need DOMDocument
        $html = "<form method=\"POST\" action=\"{$this->Wcms->url('plugins/contactform/submit.php')}\">";

        foreach((array)$this->config->fields as $field => $type) {
            if(!$type) continue;

            $html .= "<div class=\"form-group\">";
            $html .= "<label for=\"$field\">" . ucfirst($field) . "</label>";

            if(is_string($type)) {
                if($type == "captcha")
                    $html .= "[[captcha]]";
                else if($type == "textarea")
                    $html .= "<textarea class=\"form-control\" name=\"$field\" placeholder=\"" . ucfirst($field) . "\"></textarea>";
                else
                    $html .= "<input type=\"$type\" class=\"form-control\" name=\"$field\" placeholder=\"" . ucfirst($field) . "\"/>";
            } else {
                $html .= "<select class=\"form-control\" name=\"$field\">";
                $html .= "<option>Select " . ucfirst($field) . "</option>";
                $html .= "<option></option>";
                $i = 0;
                foreach($type as $item) $html .= "<option value=\"".($i++)."\">$item</option>";
                $html .= "</select>";
            }
            $html .= "</div>";
        }

        $html .= "<div><input type=\"submit\" class=\"{$this->config->submit_class}\" value=\"{$this->config->submit_value}\" /></div>";

        $html .= "</form>";

        return $html;
    }

    public function submission(array $params) {
        // Only fetch config when needed
        $this->fetchConfig();

        $data = [
            "timestamp" => date("Y-m-d H:i:s"),
            "page" => $_SERVER["HTTP_REFERER"]
        ];

        foreach((array)$this->config->fields as $field => $type) {
            // Is element enabled?
            if(!$type) continue;

            // Check if it exists
            if(!isset($params[$field])) return "Missing required field from request.";

            if(is_string($type)) {
                if($type == "captcha"){
                    // Do captcha stuff
                    // TODO: add captcha here
                    // This one looks user friendly: https://github.com/Lokno/click-captcha
                } else {
                    // Do text element stuff
                    $data[$field] = htmlentities($params[$field]);
                }
            } else {
                if(!isset($type[$params[$field]])) return "Invalid value for $field";
                $data[$field] = $type[$params[$field]];
            }
        }

        // $data now contains the whole submission

        if($this->config->send_emails) {
            $this->sendEmail($data);
        }

        if($this->config->store_locally) {
            $data_json = json_encode($data);

            if (!file_exists(__DIR__ . "/" . $this->config->path))
                mkdir(__DIR__ . "/" . $this->config->path, 0777);

            file_put_contents(__DIR__ . "/" . $this->config->path . "/" . Date("Ymd_His") . "-" . md5($data_json) . ".json", $data_json);
        }

        return false;
    }

    private function sendEmail(array $data) : void {
        $email_body = "";
        $email_to = implode(", ", array_map(function($v){return "{$v[1]} <{$v[0]}>";}, $this->config->to));
        $email_subject = "New submission from {$_SERVER['HTTP_HOST']}";
        $email_headers = [
            "From: {$this->config->from[1]} <{$this->config->from[0]}>"
        ];

        if($this->config->send_recipient && !empty($data["email"])) {
            $email_headers[] = "Bcc: " . $email_to;
            $email_to = $data["email"];
        }

        switch($this->config->format) {
            case "html":
                $email_headers[] = 'MIME-Version: 1.0';
                $email_headers[] = 'Content-type: text/html; charset=UTF-8';

                $email_body .= "<h2>$email_subject</h2>";
                $email_body .= "<table>";
                foreach($data as $name => $value) {
                    $email_body .= "<tr><th align=\"left\">".ucfirst($name)."</th><td>$value</td></tr>";
                }
                $email_body .= "</table>";
                break;
            case "plain":
                $email_body .= strtoupper($email_subject) . "\n";
                $email_body .= str_repeat("=", strlen($email_subject)) . "\n\n";

                $max_key_width = max(array_map("strlen", array_keys($data)));
                foreach($data as $name => $value) {
                    $email_body .= ucfirst($name) . str_repeat(" ", ($max_key_width - strlen($name))) . "\t" .  $value . "\n";
                }

                break;
            case "json":
                $email_body = json_encode($data);
                break;
        }

        // send email
        if($this->config->driver == "mail") {
            // Send with mail
            mail($email_to, $email_subject, $email_body, implode("\r\n", $email_headers));
        } else if($this->config->driver == "smtp") {
            // Use PHP Mailer
            require 'lib/PHPMailer/Exception.php';
            require 'lib/PHPMailer/PHPMailer.php';
            require 'lib/PHPMailer/SMTP.php';

            $mail = new PHPMailer(true);

            // Settings
            $mail->IsSMTP();
            $mail->CharSet = 'UTF-8';

            $mail->Host       = $this->config->smtp_config->server;
            $mail->SMTPAuth   = true;
            $mail->Port       = $this->config->smtp_config->port;
            $mail->Username   = $this->config->smtp_config->username;
            $mail->Password   = $this->config->smtp_config->password;

            // Content
            $mail->isHTML($this->config->format == "html");
            $mail->Subject = $email_subject;
            $mail->Body    = $email_body;

            if($this->config->send_recipient && !empty($data["email"])) {
                $mail->setFrom($this->config->from[1], $this->config->from[0]);
                $mail->addAddress($data["email"]);
                foreach($this->config->to as $to)
                    $mail->addBCC($to[1], $to[0]);
            } else {
                $mail->setFrom($this->config->from[1], $this->config->from[0]);
                foreach($this->config->to as $to)
                    $mail->addAddress($to[1], $to[0]);
            }

            $mail->send();

        }

    }



    // Util functions

    // https://www.php.net/manual/en/function.json-decode.php#112735
    private function json_clean_decode($json, $assoc = false, $depth = 512, $options = 0) {
        // search and remove comments like /* */ and //
        $json = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#", '', $json);

        if(version_compare(phpversion(), '5.4.0', '>=')) {
            $json = json_decode($json, $assoc, $depth, $options);
        }
        elseif(version_compare(phpversion(), '5.3.0', '>=')) {
            $json = json_decode($json, $assoc, $depth);
        }
        else {
            $json = json_decode($json, $assoc);
        }

        return $json;
    }

}

?>
