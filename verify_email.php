<?php

$mode = $_REQUEST['mode'];
$address = $_REQUEST['email'];

function verify_email($address, &$error) {
	$G5_SERVER_TIME = time();

	$WAIT_SECOND = 3; // ?�� ��ٸ�

	list($user, $domain) = explode("@", $address);


	// �����ο� ���� ��ȯ�Ⱑ �����ϴ��� �˻�
	if (checkdnsrr($domain, "MX")) {
		// ���� ��ȯ�� ���ڵ���� ��´�
		if (!getmxrr($domain, $mxhost, $mxweight)) {
			$error = '���� ��ȯ�⸦ ȸ���� �� ����';
			return false;
		} else {
			$_cnt = count($mxhost);
			if($_cnt == 0) {
				$mxhost[] = $domain;
				$mxweight[] = 1;
			}
		}
	} else {
		// ���� ��ȯ�Ⱑ ������, ������ ��ü�� ������ �޴� ������ ����
		$mxhost[] = $domain;
		$mxweight[] = 1;
	}

	// ���� ��ȯ�� ȣ��Ʈ�� �迭�� �����.
	for ($i=0; $i<count($mxhost); $i++)
		$weighted_host[$i] = $mxhost[$i];
	//@ksort($weighted_host);

	// �� ȣ��Ʈ�� �˻�
	foreach($weighted_host as $host) {
		// ȣ��Ʈ�� SMTP ��Ʈ�� ����
		if (!($fp = @fsockopen($host, 25))) continue;

		// 220 �޼������� �ǳʶ�
		// 3�ʰ� ������ ������ ������ ����
		socket_set_blocking($fp, false);
		$stoptime = $G5_SERVER_TIME + $WAIT_SECOND;
		$gotresponse = false;

		while (true) {
			// ���ϼ����κ��� ���� ����
			$line = fgets($fp, 1024);

			if (substr($line, 0, 3) == '220') {
				// Ÿ�̸Ӹ� �ʱ�ȭ
				$stoptime = $G5_SERVER_TIME + $WAIT_SECOND;
				$gotresponse = true;
			} else if ($line == '' && $gotresponse)
				break;
			else if ($G5_SERVER_TIME > $stoptime)
				break;
		}

		// �� ȣ��Ʈ�� ������ ����. ���� ȣ��Ʈ�� �Ѿ��
		if (!$gotresponse) continue;

		socket_set_blocking($fp, true);

		// SMTP �������� ��ȭ�� ����
		fputs($fp, "HELO {$_SERVER['SERVER_NAME']}\r\n");
		echo "HELO {$_SERVER['SERVER_NAME']}\r\n";
		fgets($fp, 1024);

		// From�� ����
		fputs($fp, "MAIL FROM: <info@$domain>\r\n");
		//echo "MAIL FROM: <info@$domain>\r\n";
		fgets($fp, 1024);

		// �ּҸ� �õ�
		fputs($fp, "RCPT TO: <$address>\r\n");
		//echo "RCPT TO: <$address>\r\n";
		$line = fgets($fp, 1024);

		// ������ ����
		fputs($fp, "QUIT\r\n");
		fclose($fp);

		if (substr($line, 0, 3) != '250') {
			// SMTP ������ �� �ּҸ� �ν����� ���ϹǷ� �߸��� �ּ���
			$error = $line;
			return false;
		} else
			// �ּҸ� �ν�����
			return true;
	}

	$error = '���� ��ȯ�⿡ �������� ���Ͽ����ϴ�.';
	return false;
}

if($mode == "verify") {
	$ret = verify_email($address, &$error);
	echo "<meta charset=\"euc-kr\">";
	if($ret) echo "<script>alert('�̸����ּ� �˻� ����');</script>";
	else echo "<script>alert('�̸����ּ� �˻� ����\\n\\n$error');</script>";
	echo "<script>location.href='verify_email.php';</script>";
	exit;
}

?>

<meta charset="euc-kr">
<title>�̸����ּ� �˻� ���α׷�</title>
<form method="post">
<input type="hidden" name="mode" value="verify">
�̸����ּ� �˻� ���α׷�<p>
�̸����ּ� <input type="text" name="email" size="20" maxlength="40" required autofocus> 
<input type="submit" value="����">
</form>
