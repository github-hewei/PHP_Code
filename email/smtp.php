<?php 
/**
 * 通过 smtp 发送邮件
 */
ini_set("date.timezone","Asia/Shanghai");
header("Content-type:text/html; charset=utf-8");
class smtp
{
    private $_relay_host;
    private $_smtp_port;
    private $_user;
    private $_pass;
    private $_host_name;
    private $_time_out;
    private $_sock;
    private $_debug;
    private $_log_file;
    private $_auth;

    public function __construct($relay_host="",$smtp_port=25,$auth=false,$user,$pass)
    {
        $this->_relay_host = $relay_host;
        $this->_smtp_port = $smtp_port;
        $this->_user = $user;
        $this->_pass = $pass;
        $this->_host_name = "localhost";
        $this->_time_out = 30;
        $this->_sock = false;
        $this->_debug = false;
        $this->_log_file = "";
        $this->_auth = $auth;
    }

    // 发送邮件
    public function sendmail($to,$from,$subject="",$body="",$mailtype,$cc="",$bcc="",$additional_headers="")
    {
        $mail_from = $this->get_address( $this->strip_comment($from) );
        $body = preg_replace("/(^|(\r\n))(\.)/","\1.\3", $body);
        $header = "MIME-Version:1.0\r\n";
        if(strtolower($mailtype)=="html") {
            $header .= "Content-type:text/html\r\n";
        }
        $header .= "To: ".$to."\r\n";
        if($cc != "") {
            $header .= "Cc: ".$cc."\r\n";
        }
        $header .= "From: $from<".$from.">\r\n";
        $header .= "Subject: ".$subject."\r\n";
        $header .= $additional_headers;
        $header .= "Date: ".date("r")."\r\n";
        $header .= "X-Mailer:By Redhat (PHP/".phpversion().")\r\n";
        list($msec, $sec) = explode(" ", microtime());
        $header .= "Message-ID: <".date("YmdHis",$sec).".".($msec*1000000).".".$mail_from.">\r\n";
        $tos = explode(",", $this->strip_comment($to));
        if($cc != "") {
            $tos = array_merge($tos, explode(",",$this->strip_comment($cc)));
        }
        if($bcc != "") {
            $tos = array_merge($tos, explode(",",$this->strip_comment($bcc)));
        }
        $sent = true;
        foreach($tos as $rcpt_to) {
            $rcpt_to = $this->get_address($rcpt_to);
            if( !$this->smtp_sockopen($rcpt_to) ) {
                $this->log_write("Error: Cannot send email to ".$rcpt_to."\n");
                $sent = false;
                continue;
            }
            if($this->smtp_send($this->_host_name,$mail_from,$rcpt_to,$header,$body)) {
                $this->log_write("E-mail has been sent to <".$rcpt_to.">\n");
            } else {
                $this->log_write("Error: Cannot send email to <".$rcpt_to.">\n");
                $sent = false;
            }
            fclose($this->_sock);
            $this->log_write("Disconnected from remote host\n");
        }
        return $sent;
    }

    public function smtp_send($helo,$from,$to,$header,$body="")
    {
        if(!$this->smtp_putcmd("HELO", $helo)) {
            return $this->smtp_error("sending HELO command");
        }
        if($this->_auth) {
            if(!$this->smtp_putcmd("AUTH LOGIN",base64_encode($this->_user))) {
                return $this->smtp_error("sending HELO command");
            }
            if(!$this->smtp_putcmd("",base64_encode($this->_pass))) {
                return $this->smtp_error("sending HELO command");
            }
        }
        if(!$this->smtp_putcmd("MAIL", "FROM:<".$from.">")) {
            return $this->smtp_error("sending MAIL FROM command");
        }
        if(!$this->smtp_putcmd("RCPT", "TO:<".$to.">")) {
            return $this->smtp_error("sending RCPT TO command");
        }
        if(!$this->smtp_putcmd("DATA")) {
            return $this->smtp_error("sending DATA command");
        }
        if(!$this->smtp_message($header,$body)) {
            return $this->smtp_error("sending message");
        }
        if(!$this->smtp_eom()) {
            return $this->smtp_error("sending <CR><LF>.<CR><LF> [EOM]");
        }
        if(!$this->smtp_putcmd("QUIT")) {
            return $this->smtp_error("sending QUIT command");
        }
        return true;
    }

    public function smtp_message($header, $body)
    {
        fputs($this->_sock, $header."\r\n".$body);
        $this->smtp_debug("> ".str_replace("\r\n","\n"."> ",$header."\n>".$body."\n>"));
        return true;
    }

    public function smtp_eom()
    {
        fputs($this->_sock, "\r\n.\r\n");
        $this->smtp_debug(". [EOM]\n");
        return $this->smtp_ok();
    }

    public function smtp_putcmd($cmd, $arg="")
    {
        if($arg != "") {
            if($cmd=="") {
                $cmd = $arg;
            } else {
                $cmd = $cmd." ".$arg;
            }
        }
        fputs($this->_sock, $cmd."\r\n");
        $this->smtp_debug("> ".$cmd."\n");
        return $this->smtp_ok();
    }

    public function smtp_sockopen($address)
    {
        if($this->_relay_host=="") {
            return $this->smtp_sockopen_mx($address);
        } else {
            return $this->smtp_sockopen_relay();
        }
    }

    public function smtp_sockopen_relay()
    {
        $this->log_write("Trying to ".$this->_relay_host.":".$this->_smtp_port."\n");
        $this->_sock = @fsockopen($this->_relay_host, $this->_smtp_port, $errno, $errstr, $this->_time_out);
        if(!($this->_sock && $this->smtp_ok())) {
            $this->log_write("Error: Cannot connenct to relay host ".$this->_relay_host."\n");
            $this->log_write("Error: ".$errstr." (".$errno.")\n");
            return false;
        }
        $this->log_write("Connected to relay host ".$this->_relay_host."\n");
        return true;
    }

    public function smtp_sockopen_mx($address)
    {
        $domain = preg_replace("^.+@([^@]+)$","\1",$address);
        if(!@getmxrr($domain, $mxhosts)) {
            $this->log_write("Error: Cannot resolve MX\"".$domain."\"\n");
        }
        foreach($mxhosts as $host) {
            $this->log_write("Trying to ".$host.":".$this->_smtp_port."\n");
            $this->_sock = @fsockopen($host,$this->_smtp_port,$errno,$errstr,$this->_time_out);
            if(! ($this->_sock && $this->smtp_ok())) {
                $this->log_write("Warning: Cannot connect to mx host ".$host."\n");
                $this->log_write("Error: ".$errstr."(".$errno.")\n");
                continue;
            }
            $this->log_write("Connected to mx host ".$host."\n");
            return true;
        }
        $this->log_write("Error: Cannot connect to any mx hosts (".implode(", ",$mxhosts).")\n");
        return false;
    }

    public function smtp_ok()
    {
        $response = str_replace("\r\n","",fgets($this->_sock, 512));
        $this->smtp_debug($response."\n");
        if(!preg_match("/^[23]/", $response)) {
            fputs($this->_sock, "QUIT\r\n");
            fgets($this->_sock, 512);
            $this->log_write("Error: Remote host returned \"".$response."\"\n");
            return false;
        }
        return true;
    }

    public function strip_comment($address)
    {
        $comment = "/\([^()]*\)/";
        while (preg_match($comment, $address)) {
            $address = preg_replace($comment,"",$address);
        }
        return $address;
    }

    public function get_address($address)
    {
        $address = preg_replace("/([ \t\r\n])+/","",$address);
        $address = preg_replace("/^.*<(.+)>.*$/","\1",$address);
        return $address;
    }

    public function log_write($message)
    {
        $this->smtp_debug($message);
        if($this->_log_file == "") {
            return true;
        }
        if(!@file_exists($this->_log_file)&&!@touch($this->_log_file)) {
            $this->smtp_debug("Warning: File does not exist and creation failed\n");
            return false;
        }
        $message = date("Y-m-d H:i:s ").get_current_user()." [".getmypid()."]: ".$message;
        if(!($fp=@fopen($this->_log_file,"a"))) {
            $this->smtp_debug("Warning: Cannot open log file \"".$this->_log_file."\"\n");
            return false;
        }
        flock($fp, LOCK_EX);
        fputs($fp, $message);
        fclose($fp);
        return true;
    }

    public function smtp_error($string)
    {
        $this->log_write("Error: Error occurred while ".$string.".\n");
        return false;
    }

    public function smtp_debug($message)
    {
        if( $this->_debug ) {
            echo $message;
        }
    }

    public function open_debug()
    {
        $this->_debug = true;
        return true;
    }
}