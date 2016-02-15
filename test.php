<?
session_set_cookie_params(86400);
session_start();
$full_home_path = dirname(__FILE__);
require_once($full_home_path.'/_rootload.php');

if ($do == "logout" and !$pass) {
        userLogOut();
        Header("Location: billing.php");
        exit;
}

mconnect();

$userBalanceEnable = GetSetting("userBalanceEnable");
$addFundsWithoutReg = GetSetting("addFundsWithoutReg");
if (($do == "addfunds" or $do == "pay") and $userBalanceEnable and $addFundsWithoutReg and !$_SESSION["userId"]) { $workWithoutAuth = 1;}

if (!$workWithoutAuth) { validateUser(); }
$EnableLanguages = EnableLanguages(); if (!$EnableLanguages) { error("Can not load languages."); mclose(); exit; }

if ($pass) {
	if (GetSetting("captcha_login") and $_SESSION['captchakey'] != $captchakeyin) {$error=$_lang[ErrorWrongCaptcha];}
	else {
	    	$res = userLogOn($login, $pass);    
		if ($res == "0" or $res == "-1") {
	                $error=$_lang[ErrorBadLoginOrPassword];
	        }
		else if ($res == "-2") {
	                $error=$_lang[ErrorUserBanned];
		}
	}
}

if (!GetCurrentCurrency()) { error($_lang[ErrorGetCurrentCurrency]); mclose(); exit; }

if (!$_SESSION["userLogin"] and !$workWithoutAuth) {
        head('utf-8',$_lang[BillingTitle]);
	?><H1 class=pagetitle><? print $_lang[BillingTitle]?></H1><hr class=hr><?

        if ($error) {print "<font color=red>".$_lang[Error].": $error</font><BR><BR>";}
        ?>
        <BR>
        <form method=post>
        <table class='rpTableBlank'>
    	 <tr><td align="right"><? print $_lang[BillingLogin]?>:</td><td><input class=input type=text name=login></tr>
    	 <tr><td align="right"><? print $_lang[BillingPassword]?>:</td><td><input class=input type=password name=pass></td></tr>
	<? if (GetSetting("lngUsersCanChange") and GetSetting("lngSelectAtLogin")) { ?>
    	 <tr><td align="right"><? print $_lang[Language]?>:</td><td><? print printLanguagesSelet()?></td></tr>
	<? } ?>
	<? if (GetSetting("captcha_login")) { ?>
 	 <tr><td></td><td class="styleHelp"><img name="captcha" src="captcha.php" align="left" style="margin-right: 10px;"> <? print $_lang[CaptchaNeVidno]?> <A class=rootlink href="" onclick="document.captcha.src = document.captcha.src + '?' + (new Date()).getTime(); return false;"><? print $_lang[CaptchaObnovit]?></a></td>
	 <tr><td align=right><? print $_lang[Captcha]?>:</td><td><input class=input type="text" name="captchakeyin" size=10 value=""></td></tr>
	<? } ?>
    	 <tr><td colspan="2"><div align="center"><input class=button type=submit value="<? print $_lang[BillingEnter]?>" name="submit"></div></td></tr>
	 <tr><td colspan=2><A class=rootlink href=forgotpass.php><? print $_lang[BillingForgotPassword]?></a></td></tr>
        </table>
        </form>
        <?
        foot('utf-8');
        mclose();
        
        exit;
}

if ($_SESSION["userId"] and !checkMobile()) {
	if ($sub == "getCode") {
		if ($mobile[0] and $mobile[1] and $mobile[2]) { $mobile[0] = preg_replace("/\+/ui","",$mobile[0]); $mobile = "+".$mobile[0]." ".$mobile[1]." ".$mobile[2]; } else { $mobile = ""; }

		if (!preg_match("/^\+\d+\s{1}\d+\s{1}\d+$/u",$mobile) or strlen($mobile) < 8) { $error = $_lang[OrderErrorField]." ".$_lang[ProfileMobile]; }
		else {
			@mysql_query("update users set mobile='$mobile' where id='$_SESSION[userId]'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

			$code = mt_rand(11111111,99999999);

			$tpl=GetTpl('email_touser_verification_sms',$_SESSION["userLang"]);
			$template=$tpl[template];

			$company_name=GetSetting('company_name');
			$company_url=GetSetting('company_url');
			$support_url=GetSetting('support_url');
        
			$template = str_replace('{company_name}',$company_name,$template);
		     	$template = str_replace('{company_url}',$company_url,$template);
		     	$template = str_replace('{support_url}',$support_url,$template);
			$template = str_replace('{code}',$code,$template);
				
			if (sendSMS($_SESSION["userId"],'',$template)) {
				@mysql_query("update users set mobileVerification='$code' where id='$_SESSION[userId]'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			}
			else { $error = $_lang[ErrorSendVerificationSMS]; }
		}
	}
	if ($sub == "checkCode" and $code) {
		$r = @mysql_query("select id from users WHERE mobileVerification='$code' and id='$_SESSION[userId]'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		if (@mysql_num_rows($r) > 0) {
			@mysql_query("update users set mobileVerification='1' where id='$_SESSION[userId]'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

			Header("Location: billing.php?".htmlDecode($goToAfter));exit;
		} 
		else { $error = $_lang[ErrorVerificationCode]; }
	}

	$user = GetUserById($_SESSION["userId"]);

        head('utf-8',$_lang[VerificationTitle]);
	print "<H1 class=pagetitle>$_lang[VerificationTitle]</H1><hr class=hr>";
        if ($error) {print "<font color=red>".$_lang[Error].": $error</font><BR><BR>";}
	print $_lang[VerificationComment]."<BR><BR>";

	if (!$goToAfter) { $goToAfter = getenv("QUERY_STRING"); }

	if ($user->mobile and $user->mobileVerification and !$needNewCode) {
		print $_lang[VerificationComment3];

		print "
	        <BR><BR>
        	<form method=post>
		<input type=hidden name=sub value=checkCode>
		<input type=hidden name=goToAfter value=\"$goToAfter\">
	        <table class='rpTableBlank'>
		<tr><td>".$_lang[VerificationCode].":</td><td><input class=input type=text name=code size=10></td></tr>
		<tr><td colspan=2><div align=center><input class=button type=submit value=\"$_lang[Next]\" name=submit></div></td></tr>
	        </table>	
        	</form>
		";

		print $_lang[VerificationComment4];

		print "
	        <BR><BR>
        	<form method=post>
		<input type=hidden name=needNewCode value=1>
		<input type=hidden name=goToAfter value=\"$goToAfter\">
	        <table class='rpTableBlank'>
		<tr><td><div align=center><input class=button type=submit value=\"$_lang[VerificationNewCode]\" name=submit></div></td></tr>
	        </table>	
        	</form>
		";
	} 
	else {
		@mysql_query("update users set mobileVerification='' where id='$_SESSION[userId]'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

		print $_lang[VerificationComment2];

		$mobile = $user->mobile;
		if ($mobile) { $mobile = @mb_split(" ",$mobile); $mobile[0] = preg_replace("/\+/iu","",$mobile[0]);} else { $mobile = array(); }

		print "
	        <BR><BR>
        	<form method=post>
		<input type=hidden name=sub value=getCode>
		<input type=hidden name=goToAfter value=\"$goToAfter\">
	        <table class='rpTableBlank'>
		<tr><td>".$_lang[ProfileMobile].":</td><td>+ <input class=input type=text name=mobile[0] maxlength=3 value=\"$mobile[0]\" title=\"".$_lang[ProfilePhoneCountryCodeComment]."\" size=1 > ( <input class=input type=text maxlength=5 name=mobile[1] value=\"$mobile[1]\" title=\"".$_lang[ProfileMobileCodeComment]."\" size=1 > ) <input class=input type=text name=mobile[2] value=\"$mobile[2]\" title=\"".$_lang[ProfilePhoneNumberComment]."\" maxlength=8 size=8 ></td></tr>
		<tr><td colspan=2><div align=center><input class=button type=submit value=\"$_lang[VerificationGetCode]\" name=submit></div></td></tr>
	        </table>	
        	</form>
		";
	}

        foot('utf-8');
        mclose();
        
        exit;
}

$weSalesTypes = GetSetting("weSalesTypes");
$weSalesTypes = @mb_split("::",$weSalesTypes);

if (!$do) {
	if (@in_array("hosting",$weSalesTypes) or @in_array("reseller",$weSalesTypes) or @in_array("vds",$weSalesTypes) or @in_array("dedicated",$weSalesTypes) or @in_array("vpn",$weSalesTypes) or @in_array("ssh",$weSalesTypes)) {
		$do="orders";
	} else if (@in_array("domains",$weSalesTypes)) {
		$do="domains";
	} else if (@in_array("shop",$weSalesTypes)) {
		$do="shop";
	} else {
		$do="bills";
	}
}

if ($do == "download" and $id) {
	$id=intval($id);

	if ($type == "ticket") {
		$ticket = @mysql_query("select * from tickets where id='$id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		$ticket = @mysql_fetch_object($ticket);

		$reply = @mysql_query("select * from tickets where parentid='$id' and id='$msgid'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		$reply = @mysql_fetch_object($reply);

		if ($ticket->userid == $_SESSION["userId"] or $reply->userid == $_SESSION["userId"]) {
			if (!$reply->id) {
				$attachedFiles = $ticket->attachedFiles;
			} else {
				$attachedFiles = $reply->attachedFiles;
			}

			if ($attachedFiles) {
				$atatchedFiles = @mb_split(":x:",$attachedFiles);
				while (list($mm,$oneFile) = @each($atatchedFiles)) {
					$oneFile = @mb_split("::",$oneFile);

					if ($file == $oneFile[1] and $oneFile[0] == "ticket_".$msgid."_".$oneFile[1]) {
						$file = $full_home_path."/_rootfiles/".$oneFile[0];
						header ("Content-Type: application/octet-stream");
						header ("Accept-Ranges: bytes");
						header ("Content-Length: ".filesize($file)); 
						$oneFile[1] = preg_replace("/ /ui","%20",$oneFile[1]);
						header ("Content-Disposition: attachment; filename=".$oneFile[1]);
						readfile($file);

						exit;
					}
				}

				error($_lang[ErrorBadId]);
			} else {
				error($_lang[ErrorBadId]);
			}
		} else {
			error($_lang[ErrorBadId]);
		}
	}
	else if ($type == "shop") {
		$order=@mysql_query("select *,TO_DAYS(NOW())-TO_DAYS(startdate) as daysFromBuy from orders_shop where id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		if (mysql_num_rows($order) > 0) {
			$order = @mysql_fetch_object($order);
			$shopItem = GetShopItemById($order->item);

			if ($shopItem->field2 and $order->daysFromBuy > $shopItem->field2) {
				error($_lang[ErrorNoMoreActiveLink]);
			} 
			else if ($shopItem->field3 and $order->field1 >= $shopItem->field3) {
				error($_lang[ErrorNoMoreDownloadCnt]);
			} else {
				$newCnt = $order->field1 + 1;
				@mysql_query("update orders_shop set field1='$newCnt' where id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

				$file = $full_home_path."/_rootsoft/".$shopItem->field1;
				header ("Content-Type: application/octet-stream");
				header ("Accept-Ranges: bytes");
				header ("Content-Length: ".filesize($file)); 
				$shopItem->field1 = preg_replace("/ /ui","%20",$shopItem->field1);
				header ("Content-Disposition: attachment; filename=".$shopItem->field1);
				readfile($file);
			}
		} else {
			error($_lang[ErrorBadId]);
		}
	}
	else {
		$id=intval($id);

		$order=mysql_query("select * from orders where id='$id' and uid='".$_SESSION["userId"]."' and archived=0") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		if (mysql_num_rows($order) > 0) {
			$order=mysql_fetch_object($order);
			if ($order->filename) {
				$file = $full_home_path."/_rootfiles/".$order->filename;
				header ("Content-Type: application/octet-stream");
				header ("Accept-Ranges: bytes");
				header ("Content-Length: ".filesize($file)); 
				$order->filename = preg_replace("/ /ui","%20",$order->filename);
				header ("Content-Disposition: attachment; filename=".$order->filename);
				readfile($file);
			} else {
				error($_lang[OrdersErrorNoFiles]);
			}
		} else {
			error($_lang[ErrorBadId]);
		}
	}

	mclose();
	exit;
}

if ($do == "gotoAccount" and $id) {
	$id=intval($id);
	$order=@mysql_query("select * from orders where id='$id' and uid='".$_SESSION["userId"]."' and archived=0") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
	if (mysql_num_rows($order) > 0) {
		$order=mysql_fetch_object($order);

		if (($order->status == "1" or ($order->testPeriod and $order->serverlogin and $order->serverpassword)) and GetSetting("enableGotoAccount")) {
			GotoUserAccount($id);
			mclose();
			exit;
		}
	}
}

if ($do == "settings") {
        if ($sub == 'edit') {
		if ($mobile[0] and $mobile[1] and $mobile[2]) { $mobile[0] = preg_replace("/\+/ui","",$mobile[0]); $mobile = "+".$mobile[0]." ".$mobile[1]." ".$mobile[2]; } else { $mobile = ""; }

		$noIdenticalMobile = GetSetting("noIdenticalMobile");
		if ($noIdenticalMobile and $mobile) {
			$mobileNumberDB = @mysql_query("select id from users WHERE mobile='$mobile' and id <> '".$_SESSION["userId"]."'");
			$mobileNumberDB = @mysql_num_rows($mobileNumberDB);
		}

		if (!valid_email($email,$VALID_EMAIL_CHECKMX)) {$error="<font color=red>".$_lang[ProfileErrorBadEmail]."</font>";}
		else if ($email2 and !valid_email($email2,$VALID_EMAIL_CHECKMX)) {$error="<font color=red>".$_lang[ProfileErrorBadEmail2]."</font>";}
		else if ($noIdenticalMobile and $mobile and $mobileNumberDB > 0) { $error="<font color=red>".$_lang[OrderErrorMobileExists]."</font>";; }
		else if ($apikey and mb_strlen($apikey) < 10) {$error="<font color=red>".$_lang[ProfileErrorApiKey]."</font>";}
		else {
	                if($passwd) {
	                        mysql_query("UPDATE users SET password = '".crypt($passwd)."' WHERE id = '".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
	                }

	                if($codeWord) {
	                        mysql_query("UPDATE users SET codeWord = '$codeWord' WHERE id = '".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
	                }

	                if($newlang) {
	                        mysql_query("UPDATE users SET lang = '$newlang' WHERE id = '".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
	                }

			if ($userCurrency != $oldCurrency) {
	                        @mysql_query("UPDATE users SET currency = '$userCurrency' WHERE id = '".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				$_SESSION["userCurrency"]=$userCurrency;
			}

			if ($oldMobile != $mobile) {
	                        @mysql_query("UPDATE users SET mobileVerification = '0' WHERE id = '".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			}

                	if ($news) {$newstomysql="1";}
                	else {$newstomysql="0";}
                	
                        @mysql_query("UPDATE users SET email = '$email', email2='$email2', news='$newstomysql', icq='$icq', skype='$skype', apikey='$apikey', wmz='$wmz', wmr='$wmr', autoRenew='$autoRenew', mobile='$mobile',smsUserBillRemind='$smsUserBillRemind',smsUserOrderRemind='$smsUserOrderRemind',smsUserOrderSuspend='$smsUserOrderSuspend',smsUserOrderDomainRemind='$smsUserOrderDomainRemind',smsUserOrderShopRemind='$smsUserOrderShopRemind',smsUserBillNew='$smsUserBillNew',smsUserTicketNew='$smsUserTicketNew',name='$name',surname='$surname',otchestvo='$otchestvo' WHERE id = '".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

                        $error=$_lang[SettinsChangeSuccess];
		}
        }

        head('utf-8',$_lang[SettinsTitle]);
	print "<H1 class=pagetitle>".$_lang[SettinsTitle]."</H1><hr class=hr>";

        if ($error) {print "$error<BR><BR>";}

        $s=@mysql_query("select * from users where id = '".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
        $t=mysql_fetch_object($s);

	if ($sub != "edit") {
		$name = $t->name;
		$surname = $t->surname;
		$otchestvo = $t->otchestvo;
		$email = $t->email;
		$email2 = $t->email2;
		$apikey = $t->apikey;
		$icq = $t->icq;
		$skype = $t->skype;
		$wmz = $t->wmz;
		$wmr = $t->wmr;
		$autoRenew = $t->autoRenew;
		$mobile = $t->mobile;
		$mobileVerification = $t->mobileVerification;
		$smsUserBillRemind = $t->smsUserBillRemind;
		$smsUserOrderRemind = $t->smsUserOrderRemind;
		$smsUserOrderDomainRemind = $t->smsUserOrderDomainRemind;
		$smsUserOrderShopRemind = $t->smsUserOrderShopRemind;
		$smsUserTicketNew = $t->smsUserTicketNew;
		$smsUserBillNew = $t->smsUserBillNew;
		$smsUserOrderSuspend = $t->smsUserOrderSuspend;
	}

	if (GetSetting("registerNeedMobile") == 2 and GetSetting("registerNeedMobileVerification") and $mobile and $mobileVerification == 1) {
		$mobileReadonly = "readonly";
		$mobileVerificationPass = "[ ".$_lang[VerificationPass]." ]";
	}

        if ($t->news) {$newscheckbox = "checked";}
        else {$newscheckbox = "";}

        echo "
        <table class='rpTable' border=0><form method=post>
        <input type=hidden name=do value=$do>
        <input type=hidden name=sub value=edit>
	<input type=hidden name=oldCurrency value='".$t->currency."'>
	<input type=hidden name=oldMobile value='".$mobile."'>
	";

	if ($mobile) { $mobile = @mb_split(" ",$mobile); $mobile[0] = preg_replace("/\+/iu","",$mobile[0]);} else { $mobile = array(); }

        print "<tr><td colspan=2 align=center class='$font_head'><B>".$_lang[ProfileUserSettings]."</b></td></tr>";
        print "<tr><td>".$_lang[ProfileCurrency].":</td><td><select class=input name=userCurrency><option>".$_lang[ProfileDefaultCurrency]."</option>";
	if (GetSetting("usersChangeCurrency")) {
		$r = GetCurrencys();
		while ($rr = @mysql_fetch_object($r)) {
			if ($rr->code == $t->currency) { $selected = "selected"; } else { $selected = ""; }
			print "<option value='$rr->code' $selected>$rr->name</option>";
		}
	}
	print "</select></td></tr>";
	if (GetSetting("lngUsersCanChange") and GetSetting("lngSelectAtProfile")) {
		print "<tr><td>".$_lang[Language].":</td><td>"; printLanguagesSelet($t->lang); print "</td></tr>";
	}
        print "<tr><td>".$_lang[ProfileNewSubscribe].":</td><td><input class=input type=checkbox $newscheckbox name=news size=40> ".$_lang[Yes]."</td></tr>";
	if (GetSetting("autoRenewClientChange")) {
		$autoRenewEnable=GetSetting("autoRenewEnable");
		if ($autoRenew == "1" or ($autoRenew == "0" and $autoRenewEnable)) {$yescheck = "checked";}
		if ($autoRenew == "2" or ($autoRenew == "0" and !$autoRenewEnable)) {$nocheck = "checked";}

	        print "<tr><td>".$_lang[ProfileAutoRenew].":</td><td><input class=input type=radio name=autoRenew value=1 $yescheck> ".$_lang[ProfileAutoRenewYes]." <input class=input type=radio name=autoRenew value=2 $nocheck> ".$_lang[ProfileAutoRenewNo]."</td></tr>";
	}
        print "<tr><td>".$_lang[ProfileWMZ].":</td><td><input class=input type=text name=wmz value=\"$wmz\" size=40></td></tr>";
        print "<tr><td>".$_lang[ProfileWMR].":</td><td><input class=input type=text name=wmr value=\"$wmr\" size=40></td></tr>";

	if ($t->api or $t->apih) {
        	print "<tr><td colspan=2 align=center class='$font_head'><B>".$_lang[ProfileApi]."</b></td></tr>
	        <tr><td>".$_lang[ProfileApiKey].":</td><td><input class=input type=text name=apikey value=\"$apikey\" size=40></td></tr>";
	}

	if (!$t->codeWord) {
		print "
        	<tr><td colspan=2 align=center class='$font_head'><B>".$_lang[ProfileCodeWord]."</b></td></tr>
	        <tr><td valign=top>".$_lang[ProfileCodeWord].":</td><td><input class=input type=text name=codeWord value=\"\" size=40><p class=\"styleHelp\">".$_lang[ProfileCodeWordComment]."</td></tr>
		";
	}

	if ($name and $surname and $otchestvo and GetSetting("noFIOChange")) { $FIOReadonly = "readonly"; } else { $FIOReadonly = ""; } 
	if ($email and GetSetting("noEmailChangeMain")) { $mainEmailReadonly = "readonly"; } else { $mainEmailReadonly = ""; } 
	if ($email2 and GetSetting("noEmailChangeAlt")) { $altEmailReadonly = "readonly"; } else { $altEmailReadonly = ""; } 

        print "<tr><td colspan=2 align=center class='$font_head'><B>".$_lang[ProfileContactInfo]."</b></td></tr>
        <tr><td>".$_lang[ProfileSurname].":</td><td><input class=input type=text name=surname value=\"$surname\" size=40 $FIOReadonly></td></tr>
        <tr><td>".$_lang[ProfileName].":</td><td><input class=input type=text name=name value=\"$name\" size=40 $FIOReadonly></td></tr>
        <tr><td>".$_lang[ProfileOtchestvo].":</td><td><input class=input type=text name=otchestvo value=\"$otchestvo\" size=40 $FIOReadonly></td></tr>
        <tr><td>".$_lang[ProfileEmail].":</td><td><input class=input type=text name=email value=\"$email\" size=40 $mainEmailReadonly></td></tr>
        <tr><td>".$_lang[ProfileEmail2].":</td><td><input class=input type=text name=email2 value=\"$email2\" size=40 $altEmailReadonly></td></tr>
        <tr><td>".$_lang[ProfileMobile].":</td><td>+ <input $mobileReadonly class=input type=text name=mobile[0] maxlength=3 value=\"$mobile[0]\" title=\"".$_lang[ProfilePhoneCountryCodeComment]."\" size=1 > ( <input $mobileReadonly class=input type=text maxlength=5 name=mobile[1] value=\"$mobile[1]\" title=\"".$_lang[ProfileMobileCodeComment]."\" size=1 > ) <input $mobileReadonly class=input type=text name=mobile[2] value=\"$mobile[2]\" title=\"".$_lang[ProfilePhoneNumberComment]."\" maxlength=8 size=8 > $mobileVerificationPass</td></tr>
        <tr><td>".$_lang[ProfileICQ].":</td><td><input class=input type=text name=icq value=\"$icq\" size=40></td></tr>
        <tr><td>".$_lang[ProfileSkype].":</td><td><input class=input type=text name=skype value=\"$skype\" size=40></td></tr>";

	if (GetSetting("smsGateway")) {
		$GsmsUserBillRemind = GetSetting("smsUserBillRemind");
		$GsmsUserOrderRemind = GetSetting("smsUserOrderRemind");
		$GsmsUserOrderDomainRemind = GetSetting("smsUserOrderDomainRemind");
		$GsmsUserOrderShopRemind = GetSetting("smsUserOrderShopRemind");
		$GsmsUserTicketNew = GetSetting("smsUserTicketNew");
		$GsmsUserBillNew = GetSetting("smsUserBillNew");
		$GsmsUserOrderSuspend = GetSetting("smsUserOrderSuspend");

		if ($GsmsUserBillRemind or $GsmsUserOrderRemind or $GsmsUserOrderDomainRemind or $GsmsUserOrderShopRemind or $GsmsUserTicketNew or $GsmsUserBillNew or $GsmsUserOrderSuspend) {
		        print "<tr><td colspan=2 align=center class='$font_head'><B>".$_lang[SettingsSMS]."</b> <img src='./_rootimages/question.gif' alt='".$_lang[SettingsSMSComment]."'></td></tr>";
			if ($GsmsUserBillRemind) { ?><tr><td colspan=2><input type=checkbox name=smsUserBillRemind value=1 <? if ($smsUserBillRemind) {print "checked";} ?>> <? print $_lang[SettingsSMSBillRemind]?></td></tr><? }
			if ($GsmsUserOrderRemind) { ?><tr><td colspan=2><input type=checkbox name=smsUserOrderRemind value=1 <? if ($smsUserOrderRemind) {print "checked";} ?>> <? print $_lang[SettingsSMSOrderRemind]?></td></tr><? }
			if ($GsmsUserOrderDomainRemind) { ?><tr><td colspan=2><input type=checkbox name=smsUserOrderDomainRemind value=1 <? if ($smsUserOrderDomainRemind) {print "checked";} ?>> <? print $_lang[SettingsSMSOrderDomainRemind]?></td></tr><? }
			if ($GsmsUserOrderShopRemind) { ?><tr><td colspan=2><input type=checkbox name=smsUserOrderShopRemind value=1 <? if ($smsUserOrderShopRemind) {print "checked";} ?>> <? print $_lang[SettingsSMSOrderShopRemind]?></td></tr><? }
			if ($GsmsUserOrderSuspend) { ?><tr><td colspan=2><input type=checkbox name=smsUserOrderSuspend value=1 <? if ($smsUserOrderSuspend) {print "checked";} ?>> <? print $_lang[SettingsSMSOrderSuspend]?></td></tr><? }
			if ($GsmsUserBillNew) { ?><tr><td colspan=2><input type=checkbox name=smsUserBillNew value=1 <? if ($smsUserBillNew) {print "checked";} ?>> <? print $_lang[SettingsSMSBillNew]?></td></tr><? }
			if ($GsmsUserTicketNew) { ?><tr><td colspan=2><input type=checkbox name=smsUserTicketNew value=1 <? if ($smsUserTicketNew) {print "checked";} ?>> <? print $_lang[SettingsSMSTicketNew]?></td></tr><? }
		}
	}

	print "<tr><td colspan=2 align=center class='$font_head'><B>".$_lang[ProfileChangePassword]."</b></td></tr>
        <tr><td>".$_lang[ProfileNewPassword].":</td><td><input class=input type=password name=passwd size=40></td></tr>";

        print "<tr><td colspan=2 align=center><BR><input class=button type=Submit value='".$_lang[Save]."'></td></tr></table><BR></form>";
       
        foot('utf-8');
}

if ($do == "profile") {
	if ($profileId == "new" and GetSetting("profileMultiEnable")) {
		@mysql_query("INSERT INTO users_profile (uid) VALUES('".$_SESSION["userId"]."')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		$profileId = mysql_insert_id();
	}

        if ($sub == 'edit') {
		if ($phone[0] and $phone[1] and $phone[2]) { $phone[0] = preg_replace("/\+/ui","",$phone[0]);  $phone = "+".$phone[0]." ".$phone[1]." ".$phone[2]; } else { $phone = ""; }
		if ($mobile[0] and $mobile[1] and $mobile[2]) { $mobile[0] = preg_replace("/\+/ui","",$mobile[0]);  $mobile = "+".$mobile[0]." ".$mobile[1]." ".$mobile[2]; } else { $mobile = ""; }
		if ($fax[0] and $fax[1] and $fax[2]) { $fax[0] = preg_replace("/\+/ui","",$fax[0]); $fax = "+".$fax[0]." ".$fax[1]." ".$fax[2]; } else { $fax = ""; }

		$object=GetUserProfileByUserId($_SESSION["userId"],$profileId);

		$upload_error = "";
		$res = array();
		if (!$upload_error and $_FILES[userfile1][name]) {
			$res[1] = uploadPassportFiles(userfile1,$object->id,"1");
			if (!$res[1]) { $upload_error = $_lang[ProfileErrorCantUpload]." 1 ($UploadError)"; }
		}
		if (!$upload_error and $_FILES[userfile2][name]) {
			$res[2] = uploadPassportFiles(userfile2,$object->id,"2");
			if (!$res[2]) { $upload_error = $_lang[ProfileErrorCantUpload]." 2 ($UploadError)"; }
		}
		if (!$upload_error and $_FILES[userfile3][name]) {
			$res[3] = uploadPassportFiles(userfile3,$object->id,"3");
			if (!$res[3]) { $upload_error = $_lang[ProfileErrorCantUpload]." 3 ($UploadError)"; }
		}
		if (!$upload_error and $_FILES[userfile4][name]) {
			$res[4] = uploadPassportFiles(userfile4,$object->id,"4");
			if (!$res[4]) { $upload_error = $_lang[ProfileErrorCantUpload]." 4 ($UploadError)"; }
		}
		if (!$upload_error and $_FILES[userfile5][name]) {
			$res[5] = uploadPassportFiles(userfile5,$object->id,"5");
			if (!$res[5]) { $upload_error = $_lang[ProfileErrorCantUpload]." 5 ($UploadError)"; }
		}
		if (!$upload_error) { if (count($res) > 0) { $passportFiles = @join(":x:",$res); }}
		else { 
			if ($res[1]) { @unlink($full_home_path."/_rootfiles/".$res[1]); }
			if ($res[2]) { @unlink($full_home_path."/_rootfiles/".$res[2]); }
			if ($res[3]) { @unlink($full_home_path."/_rootfiles/".$res[3]); }
			if ($res[4]) { @unlink($full_home_path."/_rootfiles/".$res[4]); }
			if ($res[5]) { @unlink($full_home_path."/_rootfiles/".$res[5]); }
		}

		$chkProfile = GetSetting("checkprofiletype");
		if ($isR) { $chkProfile = "max"; }
		else if ($isD and $chkProfile != "max") { $chkProfile = "min"; }
		if ($isPPUA) {$rF[] = "mobile";}

		if (!valid_email($email,$VALID_EMAIL_CHECKMX)) {$error="<font color=red>".$_lang[ProfileErrorBadEmail]."</font>";}
		else if ($upload_error) {$error="<font color=red>".$upload_error."</font>";}
		else if (checkProfile($chkProfile)) {
			if ($ripn and !preg_match("/\-RIPN$/ui",$ripn)) { $ripn = $ripn."-RIPN"; }
			if ($uanic and !preg_match("/\-UANIC$/ui",$uanic)) { $uanic = $uanic."-UANIC"; }
			if ($ripe and !preg_match("/\-RIPE$/ui",$ripe)) { $ripe = $ripe."-RIPE"; }
			if ($eunic and !preg_match("/\-EUNIC$/ui",$eunic)) { $eunic = $eunic."-EUNIC"; }
			if ($dpnic and !preg_match("/\-DPNIC$/ui",$dpnic)) { $dpnic = $dpnic."-DPNIC"; }
			if ($epnic and !preg_match("/\-EPNIC$/ui",$epnic)) { $epnic = $epnic."-EPNIC"; }

			$name = @mb_strtoupper(@mb_substr($name,0,1)).@mb_substr($name,1); $name = trim($name);
			$otchestvo = @mb_strtoupper(@mb_substr($otchestvo,0,1)).@mb_substr($otchestvo,1); $otchestvo = trim($otchestvo);
			$surname = @mb_strtoupper(@mb_substr($surname,0,1)).@mb_substr($surname,1); $surname = trim($surname);
			$firma = trim($firma);
			$firmaeng = trim($firmaeng);
			$city = trim($city);

	                @mysql_query("UPDATE users_profile SET icq = '$icq', name = '$name', otchestvo = '$otchestvo', firma = '$firma', firmaeng = '$firmaeng', phone = '$phone', mobile = '$mobile', fax = '$fax', country = '$country', oblast = '$oblast', city = '$city', post = '$post', street = '$street', address_org = '$address_org', komu = '$komu', pasport_seriya = '$seriya', pasport_by = '$by', ripn = '$ripn', ripe = '$ripe', uanic = '$uanic', eunic = '$eunic', dpnic = '$dpnic', epnic = '$epnic', surname = '$surname',pasport_date = '".fromMyDate($date)."',birthday = '".fromMyDate($birthday)."',inn = '$inn',kpp = '$kpp', okonh = '$okonh', okpo = '$okpo', bank = '$bank',bank_schet = '$bank_schet',bank_bik = '$bank_bik',edrpou='$edrpou',email='$email',org='$org',passportFiles='$passportFiles',socstrahnumber='$socstrahnumber',idnum='$idnum',ogrn='$ogrn',ogrn_by='$ogrn_by',ogrn_date='".fromMyDate($ogrn_date)."',skype='$skype' WHERE uid = '".$_SESSION["userId"]."' and id='$profileId'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

			if (GetSetting("profileSetNotChecked")) {
				@mysql_query("UPDATE users_profile SET profileChecked='0' WHERE uid = '".$_SESSION["userId"]."' and id='$profileId'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				$profileChecked = 0;
			}

	                if ($fromreg or $bill_id or ($testPeriod and $testPeriodHostId)) {
				if (GetSetting("testPeriodEnable") and $testPeriod and $testPeriodHostId) {
					if (GetSetting("testPeriodAutoCreate")) {
						if (GetSetting("orderProcessTypeHost")) {
							AddBillToQueue($bill_id, 1);
						} else {
							if (createUser($testPeriodHostId)) {
								$testPeriodCreated = 1;
							}
						}
					}
					$testAddon = "&testPeriod=1&testPeriodCreated=$testPeriodCreated";
				}

	                        Header("Location: billing.php?do=pay&id=$bill_id$testAddon");exit;
	                } else {
	                        $error=$_lang[ProfileChangeSuccess];
	                }
		} else {
			$error = "<font color=red>".$GLOBALerror."</font>";
		}
        } else {
	        $profile=GetUserProfileByUserId($_SESSION["userId"],$profileId);

		$chkProfile = GetSetting("checkprofiletype");
		if ($isR) { $chkProfile = "max"; }
		else if ($isD and $chkProfile != "max") { $chkProfile = "min"; }
		if ($isPPUA) {$rF[] = "mobile";}

		checkProfile($chkProfile, $_SESSION["userId"], $profileId);

		if ($GLOBALerror and $country and $org) {
			$error = "<font color=red>".$GLOBALerror."</font>";
			$GLOBALerrorFields = @mb_split(", ",$GLOBALerrorFields);
		}
		
	}

        head('utf-8',$_lang[ProfileTitle]);
	print "<H1 class=pagetitle>".$_lang[ProfileTitle]."</H1><hr class=hr>";

	if (!$fromreg and !$GLOBALerror) {
		print "<form method=post style='padding: 0;'><input type=hidden name=do value=$do>";
		print $_lang[ProfileYourProfiles].": "; printProfileSelect($_SESSION["userId"],$profileId,1); print " <input class=button type=Submit value='".$_lang[Select]."'>";
		print "</form>";
	}

        if ($error) {print "$error<BR><BR>";}
        if ($fromreg) {
		print "<table class='rpTableBlank' border=0><tr><td><b>".$_lang[ProfileNeedFromReg];
		if ($isD and $country) { print "<br>".$_lang[ProfileFieldsForAllDomains]; }
		if ($isR and $country) { print "<br>".$_lang[ProfileFieldsForRuDomain]; }
		if ($isPPUA and $country) { print "<br>".$_lang[ProfileFieldsForPPUADomain]; }
		if ($testPeriod) { print "<br>".$_lang[ProfileNeedForTestPeriod]; }
		print "</tr></td></table><BR>";
	}

        $object=GetUserProfileByUserId($_SESSION["userId"],$profileId);

	$t=GetUserById($_SESSION["userId"]);

	if ($sub != "edit") {
		if ($object->org and $object->country) { $org = $object->org; }
		$surname = $object->surname;
		$name = $object->name;
		$otchestvo = $object->otchestvo;
		$firma = $object->firma;
		$firmaeng = $object->firmaeng;
		$seriya = $object->pasport_seriya;
		$by = $object->pasport_by;
		$date = mydate($object->pasport_date);
		$birthday = mydate($object->birthday);
		$inn = $object->inn;
		$kpp = $object->kpp;
		if ($object->org and $object->country) { $country = $object->country; }
		$oblast = $object->oblast;
		$post = $object->post;
		$city = $object->city;
		$street = $object->street;
		$address_org = $object->address_org;
		$komu = $object->komu;
		$phone = $object->phone;
		$mobile = $object->mobile;
		$fax = $object->fax;
		$email = $object->email;
		$icq = $object->icq;
		$skype = $object->skype;
		$ripn = $object->ripn;
		$ripe = $object->ripe;
		$uanic = $object->uanic;
		$eunic = $object->eunic;
		$dpnic = $object->dpnic;
		$epnic = $object->epnic;
		$okonh = $object->okonh;
		$okpo = $object->okpo;
		$bank = $object->bank;
		$bank_schet = $object->bank_schet;
		$bank_bik = $object->bank_bik;
		$edrpou = $object->edrpou;
		$passportFiles = $object->passportFiles;
		$profileChecked = $object->profileChecked;
		$socstrahnumber = $object->socstrahnumber;
		$idnum = $object->idnum;
		$ogrn = $object->ogrn;
		$ogrn_by = $object->ogrn_by;
		$ogrn_date = mydate($object->ogrn_date);
	}

	if ($phone) { $phone = @mb_split(" ",$phone); $phone[0] = preg_replace("/\+/iu","",$phone[0]);} else { $phone = array(); if ($country == "RU") {$phone[0] = "7";} else if ($country == "UA") {$phone[0] = "380";} }
	if ($mobile) { $mobile = @mb_split(" ",$mobile); $mobile[0] = preg_replace("/\+/iu","",$mobile[0]);} else { $mobile = array(); if ($country == "RU") {$mobile[0] = "7";} else if ($country == "UA") {$mobile[0] = "380";} }
	if ($fax) { $fax = @mb_split(" ",$fax); $fax[0] = preg_replace("/\+/iu","",$fax[0]);} else { $fax = array(); if ($country == "RU") {$fax[0] = "7";} else if ($country == "UA") {$fax[0] = "380";} }
        
	if ($org == "3") { $userType = $_lang[ProfileOfOrg]; }
	else if ($org == "2") { $userType = $_lang[ProfileOfPrivatePredprinimatel]; }
	else { $userType = $_lang[ProfileOfPrivatePerson]; }

        print "
	<table class='rpTable' border=0><form method=post enctype=\"multipart/form-data\">
        <tr><td colspan=2 align=center class='$font_head'><B>$userType [$t->login]</b></td></tr>
        <input type=hidden name=do value=$do>";
        if ($country and $org) { print "<input type=hidden name=sub value=edit>"; }
        print "
	<input type=hidden name=fromreg value='$fromreg'>
        <input type=hidden name=bill_id value='$bill_id'>
        <input type=hidden name=isR value='$isR'>
        <input type=hidden name=isD value='$isD'>
        <input type=hidden name=isPPUA value='$isPPUA'>
        <input type=hidden name=profileId value='$object->id'>
        <input type=hidden name=profileChecked value='$profileChecked'>
	<input type=hidden name=testPeriod value='$testPeriod'>
	<input type=hidden name=testPeriodHostId value='$testPeriodHostId'>
	";
        if ($org) { print "<input type=hidden name=org value='$org'>"; }

	$profileDisableChange=GetSetting("profileDisableChange"); $pdc=mb_split("::",$profileDisableChange);
	$profileDisableChangeWithPassport=GetSetting("profileDisableChangeWithPassport"); $pdcwp=mb_split("::",$profileDisableChangeWithPassport);
	$profileDisableChangeWithProfileCheck=GetSetting("profileDisableChangeWithProfileCheck"); $pdcwpc=mb_split("::",$profileDisableChangeWithProfileCheck);

	if ($country and $org) {
		if ($org == "3" or ($org == "2" and $country == "FI")) { $contactTitle = $_lang[ProfileDirector]; } else if ($org == "2") { $contactTitle = $_lang[OrderUserPrivatePredprinimatel]; } else { $contactTitle = $_lang[ProfileContact]; }

		print "<tr><td colspan=2 align=center class='$font_head'><B>".$contactTitle."</b></td></tr>";

		if (@in_array("surname",$GLOBALerrorFields)) { $_lang[ProfileSurname] = "<font color=red>".$_lang[ProfileSurname]."</font>"; }
	        print "<tr><td>".$_lang[ProfileSurname].":</td><td><input class=input type=text name=surname value=\"$surname\" size=40 "; if ($surname and (@in_array("surname",$pdc) or ($profileChecked and @in_array("surname",$pdcwpc)) or ($passportFiles and @in_array("surname",$pdcwp)))) { print "readonly"; } print"></td></tr>";

		if (@in_array("name",$GLOBALerrorFields)) { $_lang[ProfileName] = "<font color=red>".$_lang[ProfileName]."</font>"; }
	        print "<tr><td>".$_lang[ProfileName].":</td><td><input class=input type=text name=name value=\"$name\" size=40 "; if ($name and (@in_array("name",$pdc) or ($profileChecked and @in_array("name",$pdcwpc)) or ($passportFiles and @in_array("name",$pdcwp)))) { print "readonly"; } print"></td></tr>";

		if (@in_array("otchestvo",$GLOBALerrorFields)) { $_lang[ProfileOtchestvo] = "<font color=red>".$_lang[ProfileOtchestvo]."</font>"; }
	        print "<tr><td>".$_lang[ProfileOtchestvo].":</td><td><input class=input type=text name=otchestvo value=\"$otchestvo\" size=40 "; if ($otchestvo and (@in_array("otchestvo",$pdc) or ($profileChecked and @in_array("otchestvo",$pdcwpc)) or ($passportFiles and @in_array("otchestvo",$pdcwp)))) { print "readonly"; } print"></td></tr>";

		if ($org == "1") {
			if ($country == "FI") {
				if (@in_array("socstrahnumber",$GLOBALerrorFields)) { $_lang[ProfileSocStrahNumber] = "<font color=red>".$_lang[ProfileSocStrahNumber]."</font>"; }
				print "<tr><td>".$_lang[ProfileSocStrahNumber].":</td><td><input class=input type=text name=socstrahnumber value=\"$socstrahnumber\" size=40 "; if ($socstrahnumber and (@in_array("socstrahnumber",$pdc) or ($profileChecked and @in_array("socstrahnumber",$pdcwpc)) or ($passportFiles and @in_array("socstrahnumber",$pdcwp)))) { print "readonly"; } print"></td></tr>";
			}
		}

		if ($org == "2") { 
			if ($country == "UA") {
				if (@in_array("edrpou",$GLOBALerrorFields)) { $_lang[ProfileEdrpou] = "<font color=red>".$_lang[ProfileEdrpou]."</font>"; }
				print "<tr><td valign=top>".$_lang[ProfileEdrpou].":</td><td><input class=input type=text name=edrpou value=\"$edrpou\" title=\"".$_lang[ProfileEdrpouComment]."\" size=40 "; if ($edrpou and (@in_array("edrpou",$pdc) or ($profileChecked and @in_array("edrpou",$pdcwpc)) or ($passportFiles and @in_array("edrpou",$pdcwp)))) { print "readonly"; } print"></td></tr>"; 
			}
			if ($country == "HU") { 
				if (@in_array("ogrn",$GLOBALerrorFields)) { $_lang[ProfileRegNumberPP] = "<font color=red>".$_lang[ProfileRegNumberPP]."</font>"; }
				print "<tr><td valign=top>".$_lang[ProfileRegNumberPP].":</td><td><input class=input type=text name=ogrn value=\"$ogrn\" title=\"".$_lang[ProfileRegNumberPPComment]."\" size=40 "; if ($ogrn and (@in_array("ogrn",$pdc) or ($profileChecked and @in_array("ogrn",$pdcwpc)) or ($passportFiles and @in_array("ogrn",$pdcwp)))) { print "readonly"; } print"></td></tr>"; 
			}

			if ($country != "UA" and $country != "BY") {
				if (@in_array("inn",$GLOBALerrorFields)) { $_lang[ProfileInn] = "<font color=red>".$_lang[ProfileInn]."</font>"; }
				print "<tr><td valign=top>".$_lang[ProfileInn].":</td><td><input class=input type=text name=inn value=\"$inn\" title=\"".$_lang[ProfileInnComment]."\" size=40 "; if ($inn and (@in_array("inn",$pdc) or ($profileChecked and @in_array("inn",$pdcwpc)) or ($passportFiles and @in_array("inn",$pdcwp)))) { print "readonly"; } print"></td></tr>"; 
			}
		}

		if (($org == "1" or $org == "2") and $country != "FI") {
			if (!GetSetting("profilePassportDisabled")) {
			        print "<tr><td colspan=2 align=center class='$font_head'><B>".$_lang[ProfilePassportData]."</b></td></tr>";

				if (@in_array("seriya",$GLOBALerrorFields)) { $_lang[ProfilePassportSeriya] = "<font color=red>".$_lang[ProfilePassportSeriya]."</font>"; }
			        print "<tr><td valign=top>".$_lang[ProfilePassportSeriya].":</td><td><input class=input type=text name=seriya value=\"$seriya\" title=\"".$_lang[ProfilePassportSeriyaComment]."\" size=40 "; if ($seriya and (@in_array("pasport_seriya",$pdc) or ($profileChecked and @in_array("pasport_seriya",$pdcwpc)) or ($passportFiles and @in_array("pasport_seriya",$pdcwp)))) { print "readonly"; } print"></td></tr>";

				if (@in_array("by",$GLOBALerrorFields)) { $_lang[ProfilePassportVidan] = "<font color=red>".$_lang[ProfilePassportVidan]."</font>"; }
		        	print "<tr><td valign=top>".$_lang[ProfilePassportVidan].":</td><td><input class=input type=text name=by value=\"$by\" title=\"".$_lang[ProfilePassportVidanComment]."\" size=40 "; if ($by and (@in_array("pasport_by",$pdc) or ($profileChecked and @in_array("pasport_by",$pdcwpc)) or ($passportFiles and @in_array("pasport_by",$pdcwp)))) { print "readonly"; } print"></td></tr>";

				if (@in_array("date",$GLOBALerrorFields)) { $_lang[ProfilePassportVidanData] = "<font color=red>".$_lang[ProfilePassportVidanData]."</font>"; }
			        print "<tr><td>".$_lang[ProfilePassportVidanData].":</td><td><input class=input type=text name=date value=\"$date\" size=40 "; if ($date and $date != "00.00.0000" and (@in_array("pasport_date",$pdc) or ($profileChecked and @in_array("pasport_date",$pdcwpc)) or ($passportFiles and @in_array("pasport_date",$pdcwp)))) { print "readonly"; } print"></td></tr>";

				if (@in_array("birthday",$GLOBALerrorFields)) { $_lang[ProfileBirthDate] = "<font color=red>".$_lang[ProfileBirthDate]."</font>"; }
			        print "<tr><td>".$_lang[ProfileBirthDate].":</td><td><input class=input type=text name=birthday value=\"$birthday\" size=40 "; if ($birthday and $birthday != "00.00.0000" and (@in_array("birthday",$pdc) or ($profileChecked and @in_array("birthday",$pdcwpc)) or ($passportFiles and @in_array("birthday",$pdcwp)))) { print "readonly"; } print"></td></tr>";

				if ($country == "BY") {
					if (@in_array("idnum",$GLOBALerrorFields)) { $_lang[ProfilePassportIdnum] = "<font color=red>".$_lang[ProfilePassportIdnum]."</font>"; }
			        	print "<tr><td valign=top>".$_lang[ProfilePassportIdnum].":</td><td><input class=input type=text name=idnum value=\"$idnum\" title=\"".$_lang[ProfilePassportIdnumComment]."\" size=40 "; if ($idnum and (@in_array("idnum",$pdc) or ($profileChecked and @in_array("idnum",$pdcwpc)) or ($passportFiles and @in_array("idnum",$pdcwp)))) { print "readonly"; } print"></td></tr>";
				}
			}
		}

		if ($org == "3" or ($org == "2" and ($country == "FI" or $country == "BY"))) {
			if ($org == "2" and $country == "BY") {
			        print "<tr><td colspan=2 align=center class='$font_head'><B>".$_lang[ProfileRegData]."</b></td></tr>";
			} else {
			        print "<tr><td colspan=2 align=center class='$font_head'><B>".$_lang[ProfileOrgData]."</b></td></tr>";
			}

			if (!($org == "2" and $country == "BY")) {
				if (@in_array("firma",$GLOBALerrorFields)) { $_lang[ProfileOrg] = "<font color=red>".$_lang[ProfileOrg]."</font>"; }
			        print "<tr><td valign=top>".$_lang[ProfileOrg].":</td><td><input class=input type=text name=firma value=\"$firma\" title='".$_lang[ProfileOrgComment]."' size=40 "; if ($firma and (@in_array("firma",$pdc) or ($profileChecked and @in_array("firma",$pdcwpc)) or ($passportFiles and @in_array("firma",$pdcwp)))) { print "readonly"; } print"></td></tr>";

			        if ($country != "FI") { 
					if (@in_array("firmaeng",$GLOBALerrorFields)) { $_lang[ProfileOrgEng] = "<font color=red>".$_lang[ProfileOrgEng]."</font>"; }
					print "<tr><td valign=top>".$_lang[ProfileOrgEng].":</td><td><input class=input type=text name=firmaeng value=\"$firmaeng\" title='".$_lang[ProfileOrgEngComment]."' size=40 "; if ($firmaeng and (@in_array("firmaeng",$pdc) or ($profileChecked and @in_array("firmaeng",$pdcwpc)) or ($passportFiles and @in_array("firmaeng",$pdcwp)))) { print "readonly"; } print"></td></tr>"; 
				}

				if (@in_array("address_org",$GLOBALerrorFields)) { $_lang[ProfileYuridAddress] = "<font color=red>".$_lang[ProfileYuridAddress]."</font>"; }
			        print "<tr><td valign=top>".$_lang[ProfileYuridAddress].":</td><td><input class=input type=text name=address_org value=\"$address_org\" title=\"".$_lang[ProfileYuridAddressComment]."\" size=40 "; if ($address_org and (@in_array("address_org",$pdc) or ($profileChecked and @in_array("address_org",$pdcwpc)) or ($passportFiles and @in_array("address_org",$pdcwp)))) { print "readonly"; } print"></td></tr>";
			}

		        if ($country != "FI") { 
				if (@in_array("inn",$GLOBALerrorFields)) { $_lang[ProfileInn] = "<font color=red>".$_lang[ProfileInn]."</font>"; }
				print "<tr><td valign=top>".$_lang[ProfileInn].":</td><td><input class=input type=text name=inn value=\"$inn\" title=\"".$_lang[ProfileInnComment2]."\" size=40 "; if ($inn and (@in_array("inn",$pdc) or ($profileChecked and @in_array("inn",$pdcwpc)) or ($passportFiles and @in_array("inn",$pdcwp)))) { print "readonly"; } print"></td></tr>"; 
			}

		        if ($country == "RU") { 
				if (@in_array("kpp",$GLOBALerrorFields)) { $_lang[ProfileKpp] = "<font color=red>".$_lang[ProfileKpp]."</font>"; }
				print "<tr><td valign=top>".$_lang[ProfileKpp].":</td><td><input class=input type=text name=kpp value=\"$kpp\" title=\"".$_lang[ProfileKppComment]."\" size=40 "; if ($kpp and (@in_array("kpp",$pdc) or ($profileChecked and @in_array("kpp",$pdcwpc)) or ($passportFiles and @in_array("kpp",$pdcwp)))) { print "readonly"; } print"></td></tr>"; 

				if (@in_array("okonh",$GLOBALerrorFields)) { $_lang[ProfileOkonh] = "<font color=red>".$_lang[ProfileOkonh]."</font>"; }
				print "<tr><td valign=top>".$_lang[ProfileOkonh].":</td><td><input class=input type=text name=okonh value=\"$okonh\" title=\"".$_lang[ProfileOkonhComment]."\" size=40 "; if ($okonh and (@in_array("okonh",$pdc) or ($profileChecked and @in_array("okonh",$pdcwpc)) or ($passportFiles and @in_array("okonh",$pdcwp)))) { print "readonly"; } print"></td></tr>"; 
			}

		        if ($country == "BY") { 
				if (@in_array("okpo",$GLOBALerrorFields)) { $_lang[ProfileOkpo] = "<font color=red>".$_lang[ProfileOkpo]."</font>"; }
				print "<tr><td valign=top>".$_lang[ProfileOkpo].":</td><td><input class=input type=text name=okpo value=\"$okpo\" title=\"".$_lang[ProfileOkpoComment]."\" size=40 "; if ($okpo and (@in_array("okpo",$pdc) or ($profileChecked and @in_array("okpo",$pdcwpc)) or ($passportFiles and @in_array("okpo",$pdcwp)))) { print "readonly"; } print"></td></tr>"; 
			}
		        
			if (@in_array("ogrn",$GLOBALerrorFields)) { $_lang[ProfileOgrn] = "<font color=red>".$_lang[ProfileOgrn]."</font>"; }
			print "<tr><td valign=top>".$_lang[ProfileOgrn].":</td><td><input class=input type=text name=ogrn value=\"$ogrn\" title=\"".$_lang[ProfileOgrnComment]."\" size=40 "; if ($ogrn and (@in_array("ogrn",$pdc) or ($profileChecked and @in_array("ogrn",$pdcwpc)) or ($passportFiles and @in_array("ogrn",$pdcwp)))) { print "readonly"; } print"></td></tr>";

			if (@in_array("ogrn_by",$GLOBALerrorFields)) { $_lang[ProfileOgrnBy] = "<font color=red>".$_lang[ProfileOgrnBy]."</font>"; }
			print "<tr><td valign=top>".$_lang[ProfileOgrnBy].":</td><td><input class=input type=text name=ogrn_by value=\"$ogrn_by\" title=\"".$_lang[ProfileOgrnByComment]."\" size=40 "; if ($ogrn_by and (@in_array("ogrn_by",$pdc) or ($profileChecked and @in_array("ogrn_by",$pdcwpc)) or ($passportFiles and @in_array("ogrn_by",$pdcwp)))) { print "readonly"; } print"></td></tr>";

			if (@in_array("ogrn_date",$GLOBALerrorFields)) { $_lang[ProfileOgrnDate] = "<font color=red>".$_lang[ProfileOgrnDate]."</font>"; }
			print "<tr><td valign=top>".$_lang[ProfileOgrnDate].":</td><td><input class=input type=text name=ogrn_date value=\"$ogrn_date\" title=\"".$_lang[ProfileOgrnDateComment]."\" size=40 "; if ($ogrn_date and (@in_array("ogrn_date",$pdc) or ($profileChecked and @in_array("ogrn_date",$pdcwpc)) or ($passportFiles and @in_array("ogrn_date",$pdcwp)))) { print "readonly"; } print"></td></tr>";

		}

		$passportFilesEnable = GetSetting("passportFilesEnable");

		if ($passportFilesEnable and !GetSetting("profilePassportDisabled")) {
			if (!$passportFiles) {
				if ($org == "1") { $commPassport = $_lang[ProfilePassportFilesUser]; } else if ($org == "2") { $commPassport = $_lang[ProfilePassportFilesIP]; } else if ($org == "3") { $commPassport = $_lang[ProfilePassportFilesOrg]; }
				if (@in_array("passportFiles",$GLOBALerrorFields)) { $_lang[ProfilePassportFiles] = "<font color=red>".$_lang[ProfilePassportFiles]."</font>"; }
				print "<tr><td valign=top>".$_lang[ProfilePassportFiles].":</td><td><p class=\"styleHelp\"><B>".$commPassport."</B><BR>".$_lang[ProfilePassportFilesComment]."<BR>".$_lang[ProfileImage]."1: <input type='file' class='input' name='userfile1'><BR>".$_lang[ProfileImage]."2: <input type='file' class='input' name='userfile2'><BR>".$_lang[ProfileImage]."3: <input type='file' class='input' name='userfile3'><BR>".$_lang[ProfileImage]."4: <input type='file' class='input' name='userfile4'><BR>".$_lang[ProfileImage]."5: <input type='file' class='input' name='userfile5'></td></tr>";
			} else {
				print "<input type=hidden name=passportFiles value='$passportFiles'>";
			}
		}


		if ($org == "2" or $org == "3") {
		        print "<tr><td colspan=2 align=center class='$font_head'><B>".$_lang[ProfileBankRekviz]."</b></td></tr>";
			if (@in_array("bank",$GLOBALerrorFields)) { $_lang[ProfileBank] = "<font color=red>".$_lang[ProfileBank]."</font>"; }
        		print "<tr><td valign=top>".$_lang[ProfileBank].":</td><td><input class=input type=text name=bank value=\"$bank\" size=40 "; if ($bank and (@in_array("bank",$pdc) or ($profileChecked and @in_array("bank",$pdcwpc)) or ($passportFiles and @in_array("bank",$pdcwp)))) { print "readonly"; } print"></td></tr>";
			if (@in_array("bank_bik",$GLOBALerrorFields)) { $_lang[ProfileBik] = "<font color=red>".$_lang[ProfileBik]."</font>"; }
        		print "<tr><td valign=top>".$_lang[ProfileBik].":</td><td><input class=input type=text name=bank_bik value=\"$bank_bik\" size=40 "; if ($bank_bik and (@in_array("bank_bik",$pdc) or ($profileChecked and @in_array("bank_bik",$pdcwpc)) or ($passportFiles and @in_array("bank_bik",$pdcwp)))) { print "readonly"; } print"></td></tr>";
			if (@in_array("bank_schet",$GLOBALerrorFields)) { $_lang[ProfileSchet] = "<font color=red>".$_lang[ProfileSchet]."</font>"; }
	        	print "<tr><td valign=top>".$_lang[ProfileSchet].":</td><td><input class=input type=text name=bank_schet value=\"$bank_schet\" size=40 "; if ($bank_schet and (@in_array("bank_schet",$pdc) or ($profileChecked and @in_array("bank_schet",$pdcwpc)) or ($passportFiles and @in_array("bank_schet",$pdcwp)))) { print "readonly"; } print"></td></tr>";
		}

		if ($country and (@in_array("country",$pdc) or ($profileChecked and @in_array("country",$pdcwpc)) or ($passportFiles and @in_array("country",$pdcwp)))) { $countryReadonly=1; } else {$countryReadonly=0;}
		if ($oblast and (@in_array("oblast",$pdc) or ($profileChecked and @in_array("oblast",$pdcwpc)) or ($passportFiles and @in_array("oblast",$pdcwp)))) { $oblastReadonly=1; } else {$oblastReadonly=0;}

		if (@in_array("country",$GLOBALerrorFields)) { $_lang[ProfileCountry] = "<font color=red>".$_lang[ProfileCountry]."</font>"; }
		if (@in_array("oblast",$GLOBALerrorFields)) { $_lang[ProfileOblast] = "<font color=red>".$_lang[ProfileOblast]."</font>"; }
		if (@in_array("post",$GLOBALerrorFields)) { $_lang[ProfileIndex] = "<font color=red>".$_lang[ProfileIndex]."</font>"; }
		if (@in_array("city",$GLOBALerrorFields)) { $_lang[ProfileCity] = "<font color=red>".$_lang[ProfileCity]."</font>"; }
		if (@in_array("street",$GLOBALerrorFields)) { $_lang[ProfileAddress] = "<font color=red>".$_lang[ProfileAddress]."</font>"; }

		print "<tr><td colspan=2 align=center class='$font_head'><B>".$_lang[ProfilePochtAddress]."</b></td></tr>
		<tr><td>".$_lang[ProfileCountry].":</td><td>"; printCountrySelect($country,$countryReadonly); echo "</td></tr>
        	<tr><td valign=top>".$_lang[ProfileOblast].":</td><td>"; printOblastSelect($country, $oblast, $oblastReadonly); print "</td></tr>
	        <tr><td valign=top>".$_lang[ProfileIndex].":</td><td><input class=input type=text name=post value=\"$post\" title=\"".$_lang[ProfileIndexComment]."\" size=40 "; if ($post and (@in_array("post",$pdc) or ($profileChecked and @in_array("post",$pdcwpc)) or ($passportFiles and @in_array("post",$pdcwp)))) { print "readonly"; } print"></td></tr>
        	<tr><td valign=top>".$_lang[ProfileCity].":</td><td><input class=input type=text name=city value=\"$city\" title=\"".$_lang[ProfileCityComment]."\" size=40 "; if ($city and (@in_array("city",$pdc) or ($profileChecked and @in_array("city",$pdcwpc)) or ($passportFiles and @in_array("city",$pdcwp)))) { print "readonly"; } print"></td></tr>
	        <tr><td valign=top>".$_lang[ProfileAddress].":</td><td><input class=input type=text name=street value=\"$street\" title=\"".$_lang[ProfileAddressComment]."\" size=40 "; if ($street and (@in_array("street",$pdc) or ($profileChecked and @in_array("street",$pdcwpc)) or ($passportFiles and @in_array("street",$pdcwp)))) { print "readonly"; } print"></td></tr>";
        	if ($country != "FI") { 
			if (@in_array("komu",$GLOBALerrorFields)) { $_lang[ProfileKomu] = "<font color=red>".$_lang[ProfileKomu]."</font>"; }
			print "<tr><td valign=top>".$_lang[ProfileKomu].":</td><td><input class=input type=text name=komu value=\"$komu\" title=\"".$_lang[ProfileKomuComment]."\" size=40 "; if ($komu and (@in_array("komu",$pdc) or ($profileChecked and @in_array("komu",$pdcwpc)) or ($passportFiles and @in_array("komu",$pdcwp)))) { print "readonly"; } print"></td></tr>"; 
		}

		if (@in_array("phone",$GLOBALerrorFields)) { $_lang[ProfilePhone] = "<font color=red>".$_lang[ProfilePhone]."</font>"; }
		if (@in_array("mobile",$GLOBALerrorFields)) { $_lang[ProfileMobile] = "<font color=red>".$_lang[ProfileMobile]."</font>"; }
		if (@in_array("fax",$GLOBALerrorFields)) { $_lang[ProfileFax] = "<font color=red>".$_lang[ProfileFax]."</font>"; }
		if (@in_array("email",$GLOBALerrorFields)) { $_lang[ProfileEmail] = "<font color=red>".$_lang[ProfileEmail]."</font>"; }
		if (@in_array("icq",$GLOBALerrorFields)) { $_lang[ProfileIcq] = "<font color=red>".$_lang[ProfileIcq]."</font>"; }

	        print "<tr><td colspan=2 align=center class='$font_head'><B>".$_lang[ProfileContactInfo]."</b></td></tr>
	        <tr><td valign=top>".$_lang[ProfilePhone].":</td><td>+ <input class=input type=text name=phone[0] maxlength=3 value=\"$phone[0]\" title=\"".$_lang[ProfilePhoneCountryCodeComment]."\" size=1 "; if ($phone[0] and $phone[1] and $phone[2] and (@in_array("phone",$pdc) or ($profileChecked and @in_array("phone",$pdcwpc)) or ($passportFiles and @in_array("phone",$pdcwp)))) { print "readonly"; } print"> ( <input class=input type=text maxlength=5 name=phone[1] value=\"$phone[1]\" title=\"".$_lang[ProfilePhoneCodeComment]."\" size=1 "; if ($phone[0] and $phone[1] and $phone[2] and (@in_array("phone",$pdc) or ($profileChecked and @in_array("phone",$pdcwpc)) or ($passportFiles and @in_array("phone",$pdcwp)))) { print "readonly"; } print"> ) <input class=input type=text name=phone[2] value=\"$phone[2]\" title=\"".$_lang[ProfilePhoneNumberComment]."\" maxlength=8 size=5 "; if ($phone[0] and $phone[1] and $phone[2] and (@in_array("phone",$pdc) or ($profileChecked and @in_array("phone",$pdcwpc)) or ($passportFiles and @in_array("phone",$pdcwp)))) { print "readonly"; } print"></td></tr>
	        <tr><td valign=top>".$_lang[ProfileMobile].":</td><td>+ <input class=input type=text name=mobile[0] maxlength=3 value=\"$mobile[0]\" title=\"".$_lang[ProfilePhoneCountryCodeComment]."\" size=1 "; if ($mobile[0] and $mobile[1] and $mobile[2] and (@in_array("mobile",$pdc) or ($profileChecked and @in_array("mobile",$pdcwpc)) or ($passportFiles and @in_array("mobile",$pdcwp)))) { print "readonly"; } print"> ( <input class=input type=text maxlength=5 name=mobile[1] value=\"$mobile[1]\" title=\"".$_lang[ProfileMobileCodeComment]."\" size=1 "; if ($mobile[0] and $mobile[1] and $mobile[2] and (@in_array("mobile",$pdc) or ($profileChecked and @in_array("mobile",$pdcwpc)) or ($passportFiles and @in_array("mobile",$pdcwp)))) { print "readonly"; } print"> ) <input class=input type=text name=mobile[2] value=\"$mobile[2]\" title=\"".$_lang[ProfilePhoneNumberComment]."\" maxlength=8 size=5 "; if ($mobile[0] and $mobile[1] and $mobile[2] and (@in_array("mobile",$pdc) or ($profileChecked and @in_array("mobile",$pdcwpc)) or ($passportFiles and @in_array("mobile",$pdcwp)))) { print "readonly"; } print"></td></tr>
	        <tr><td valign=top>".$_lang[ProfileFax].":</td><td>+ <input class=input type=text name=fax[0] maxlength=3 value=\"$fax[0]\" title=\"".$_lang[ProfilePhoneCountryCodeComment]."\" size=1 "; if ($fax[0] and $fax[1] and $fax[2] and (@in_array("fax",$pdc) or ($profileChecked and @in_array("fax",$pdcwpc)) or ($passportFiles and @in_array("fax",$pdcwp)))) { print "readonly"; } print"> ( <input class=input type=text maxlength=5 name=fax[1] value=\"$fax[1]\" title=\"".$_lang[ProfilePhoneCodeComment]."\" size=1 "; if ($fax[0] and $fax[1] and $fax[2] and (@in_array("fax",$pdc) or ($profileChecked and @in_array("fax",$pdcwpc)) or ($passportFiles and @in_array("fax",$pdcwp)))) { print "readonly"; } print"> ) <input class=input type=text name=fax[2] value=\"$fax[2]\" title=\"".$_lang[ProfileFaxNumberComment]."\" maxlength=8 size=5 "; if ($fax[0] and $fax[1] and $fax[2] and (@in_array("fax",$pdc) or ($profileChecked and @in_array("fax",$pdcwpc)) or ($passportFiles and @in_array("fax",$pdcwp)))) { print "readonly"; } print"></td></tr>
	        <tr><td>".$_lang[ProfileEmail].":</td><td><input class=input type=text name=email value=\"$email\" size=40 "; if ($email and (@in_array("email",$pdc) or ($profileChecked and @in_array("email",$pdcwpc)) or ($passportFiles and @in_array("email",$pdcwp)))) { print "readonly"; } print"></td></tr>
        	<tr><td>".$_lang[ProfileIcq].":</td><td><input class=input type=text name=icq value=\"$icq\" size=40 "; if ($icq and (@in_array("icq",$pdc) or ($profileChecked and @in_array("icq",$pdcwpc)) or ($passportFiles and @in_array("icq",$pdcwp)))) { print "readonly"; } print"></td></tr>
        	<tr><td>".$_lang[ProfileSkype].":</td><td><input class=input type=text name=skype value=\"$skype\" size=40 "; if ($skype and (@in_array("skype",$pdc) or ($profileChecked and @in_array("skype",$pdcwpc)) or ($passportFiles and @in_array("skype",$pdcwp)))) { print "readonly"; } print"></td></tr>

	        <tr><td colspan=2 align=center class='$font_head'><B>".$_lang[ProfileDopInfo]."</b></td></tr>
        	<tr><td>".$_lang[ProfileRipn].":</td><td><input class=input type=text name=ripn value=\"$ripn\" size=40 "; if ($ripn and (@in_array("ripn",$pdc) or ($profileChecked and @in_array("ripn",$pdcwpc)) or ($passportFiles and @in_array("ripn",$pdcwp)))) { print "readonly"; } print"></td></tr>
	        <tr><td>".$_lang[ProfileRipe].":</td><td><input class=input type=text name=ripe value=\"$ripe\" size=40 "; if ($ripe and (@in_array("ripe",$pdc) or ($profileChecked and @in_array("ripe",$pdcwpc)) or ($passportFiles and @in_array("ripe",$pdcwp)))) { print "readonly"; } print"></td></tr>
        	<tr><td>".$_lang[ProfileUanic].":</td><td><input class=input type=text name=uanic value=\"$uanic\" size=40 "; if ($uanic and (@in_array("uanic",$pdc) or ($profileChecked and @in_array("uanic",$pdcwpc)) or ($passportFiles and @in_array("uanic",$pdcwp)))) { print "readonly"; } print"></td></tr>
        	<tr><td>".$_lang[ProfileEunic].":</td><td><input class=input type=text name=eunic value=\"$eunic\" size=40 "; if ($eunic and (@in_array("eunic",$pdc) or ($profileChecked and @in_array("eunic",$pdcwpc)) or ($passportFiles and @in_array("eunic",$pdcwp)))) { print "readonly"; } print"></td></tr>
        	<tr><td>".$_lang[ProfileDpnic].":</td><td><input class=input type=text name=dpnic value=\"$dpnic\" size=40 "; if ($dpnic and (@in_array("dpnic",$pdc) or ($profileChecked and @in_array("dpnic",$pdcwpc)) or ($passportFiles and @in_array("dpnic",$pdcwp)))) { print "readonly"; } print"></td></tr>
        	<tr><td>".$_lang[ProfileEpnic].":</td><td><input class=input type=text name=epnic value=\"$epnic\" size=40 "; if ($epnic and (@in_array("epnic",$pdc) or ($profileChecked and @in_array("epnic",$pdcwpc)) or ($passportFiles and @in_array("epnic",$pdcwp)))) { print "readonly"; } print"></td></tr>
		";
	} else {
		$profileTypes=GetSetting("profileTypes"); $profileTypes=mb_split("::",$profileTypes);

		if ($org == "1") {$orgCheck1='checked';} else if ($org == "2") {$orgCheck2='checked';}  else if ($org == "3") {$orgCheck3='checked';}
		print "<tr><td>".$_lang[ProfileUserType].":</td><td>";
		if (@in_array("1",$profileTypes)) { print "<input type=radio name=org value=1 $orgCheck1> ".$_lang[OrderUserPrivatePerson]." "; }
		if (@in_array("2",$profileTypes)) { print "<input type=radio name=org value=2 $orgCheck2> ".$_lang[OrderUserPrivatePredprinimatel]." "; }
		if (@in_array("3",$profileTypes)) { print "<input type=radio name=org value=3 $orgCheck3> ".$_lang[OrderUserOrg]; }
		print "</td></tr>";
		print "<tr><td>".$_lang[ProfileCountry].":</td><td>"; printCountrySelect($country); echo "</td></tr>";
	}

        print "<tr><td colspan=2 align=center><BR><input class=button type=Submit value='".$_lang[Save]."'></td></tr></table><BR></form>";
       
        foot('utf-8');
}

if (!$workWithoutAuth) {
	if ($do != "profile" and $do != "tickets" and $do != "settings" and !checkSettings($_SESSION["userId"])) {
		$do = "";

		error($_lang[SettingsErrorYouCanWorkWithoutSettings]." (".$GLOBALerror.")");
	}
	else if ($do != "profile" and $do != "tickets" and $do != "settings" and !checkProfile(GetSetting("checkprofiletype"), $_SESSION["userId"])) {
		$do = "";

		$addButton = "<form action=billing.php method=post><input type=hidden name=do value=profile><input type=submit class=input value='".$_lang["ButtonFillProfile"]."'></form>";
		error($_lang[ProfileErrorYouCanWorkWithoutProfile], $addButton);
	}
	else if ($do != "profile" and $do != "tickets" and $do != "settings" and !checkProfileByAdmin($_SESSION["userId"])) {
		$do = "";

		error($_lang[ProfileErrorYouCanWorkWithoutCheckedProfile]);
	}
}

if ($do == "orders") {
	if (($sub == "dogovor" or $sub == "dodatok") and ($host_id or $domain_id or $shop_id)) {
		$host_id = intval($host_id);
		$domain_id = intval($domain_id);
		$shop_id = intval($shop_id);

		if ((GetSetting("dogovor_enable") and ($host_id or $domain_id)) or (GetSetting("dogovor_shop_enable") and $shop_id)) {
			if ($sub == "dogovor") {
				$kvtpl = GetTpl("tpl_dogovor",$_SESSION["userLang"]);
				$kvtpl = $kvtpl[template];
			}
			if ($sub == "dodatok") {
				$kvtpl = GetTpl("tpl_dogovor_dodatok",$_SESSION["userLang"]);
				$kvtpl = $kvtpl[template];
			}

			$dogovor_gorod=GetSetting("dogovor_gorod");
			$dogovor_prodavec=htmlEncode(GetSetting("dogovor_prodavec"));
			$dogovor_svidetelstvo=GetSetting("dogovor_svidetelstvo");
			$dogovor_terms=GetSetting("dogovor_terms");
			$dogovor_rekviziti=GetSetting("dogovor_rekviziti"); $dogovor_rekviziti = preg_replace("/\n/ui","<BR>",$dogovor_rekviziti);
			$dogovor_email=GetSetting("dogovor_email");
			$dogovor_logo=GetSetting("dogovor_logo"); if ($dogovor_logo) {$dogovor_logo = "<img src=\"./_rootimages/".$dogovor_logo."\">";}
			$dogovor_shtamp=GetSetting("dogovor_shtamp"); if ($dogovor_shtamp) {$dogovor_shtamp = "<img src=\"./_rootimages/".$dogovor_shtamp."\">";}

			if ($host_id) {
				$order=GetOrderById($host_id);
				if ($order->uid != $_SESSION["userId"]) {exit;}

				$tarif=GetTarifById($order->tarif);
				$dogovor_tarif = $tarif->name;

				if ($order->todate != "0000-00-00") {
					$orderToDate=$order->todate; $orderToDate=mb_split("\-",$orderToDate);
					$dogovor_hosttodate = $orderToDate[2].".".$orderToDate[1].".".$orderToDate[0];
				} else {
					$dogovor_hosttodate = "__________";
				}

				if ($order->domain_reg == 1 or $order->domain_reg == 3) {
					$dogovor_domain = $order->domain;

					$orderdDomain = GetDomainByDomain($order->domain);
					if ($orderdDomain->todate != "0000-00-00") {
						$orderToDate=$orderdDomain->todate; $orderToDate=mb_split("\-",$orderToDate);
						$dogovor_domaintodate = $orderToDate[2].".".$orderToDate[1].".".$orderToDate[0];
					} else {
						$dogovor_domaintodate = "__________";
					}
				} else {
					$dogovor_domain = "__________";
					$dogovor_domaintodate = "__________";
				}

				$dogovor_shopitem = "__________";
				$dogovor_shopitemtodate = "__________";
			}
			else if ($domain_id) {
				$order=GetDomainById($domain_id);
				if ($order->uid != $_SESSION["userId"]) {exit;}

				$dogovor_domain = $order->domain;

				if ($order->todate != "0000-00-00") {
					$orderToDate=$order->todate; $orderToDate=mb_split("\-",$orderToDate);
					$dogovor_domaintodate = $orderToDate[2].".".$orderToDate[1].".".$orderToDate[0];
				} else {
					$dogovor_domaintodate = "__________";
				}

				$dogovor_tarif = "__________";
				$dogovor_hosttodate = "__________";
				$dogovor_shopitem = "__________";
				$dogovor_shopitemtodate = "__________";
			}
			else if ($shop_id) {
				$order=GetOrderShopById($shop_id);
				if ($order->uid != $_SESSION["userId"]) {exit;}
				$item=GetShopItemById($order->item);

				$dogovor_shopitem = $item->name;

				if ($order->todate != "0000-00-00") {
					$orderToDate=$order->todate; $orderToDate=mb_split("\-",$orderToDate);
					$dogovor_shopitemtodate = $orderToDate[2].".".$orderToDate[1].".".$orderToDate[0];
				} else {
					$dogovor_shopitemtodate = "__________";
				}

				$dogovor_tarif = "__________";
				$dogovor_hosttodate = "__________";
				$dogovor_domain = "__________";
				$dogovor_domaintodate = "__________";
			}

			if ($order->status == "0") { $dogovor_shtamp = ""; }

			$user=GetUserById($order->uid);

			if ($domain_id and GetSetting("dogovorForDomainWithProfileInfo")) {
				$profile=GetUserProfileByUserId($order->uid,$order->profileId);
			} else {
				$profile=GetUserProfileByUserId($order->uid);
			}

			$orderDate=$order->orderdate; $orderDate=mb_split("\-",$orderDate);
			if ($profile->org == "3") {
				$dogovor_name = $profile->firma;
				$dogovor_zakazchik = $dogovor_name." в лице ".$profile->surname." ".$profile->name." ".$profile->otchestvo; 
				$address=$profile->address_org;
				if ($profile->bank and $profile->bank_schet and $profile->bank_bik) {
					if ($profile->country == "RU") {$bik="Б�?К";}
					else {$bik="МФО";}

					$bankrekviziti = "� /с ".$profile->bank_schet."<BR>";
					$bankrekviziti = $bankrekviziti.$profile->bank."<BR>";
					$bankrekviziti = $bankrekviziti."$bik ".$profile->bank_bik;
					if ($profile->edrpou) { $bankrekviziti = $bankrekviziti." ".$_lang[ProfileEdrpou]." ".$profile->edrpou; }
					if ($profile->inn) { $bankrekviziti = $bankrekviziti." ".$_lang[ProfileInn]." ".$profile->inn; }
				}
			} else { 
				$dogovor_name = $profile->surname." ".$profile->name." ".$profile->otchestvo;
				$dogovor_zakazchik = $dogovor_name;
				$address=$profile->post.", ".$profile->city.", ".$profile->street;
			}

			$dogovor_clientrekviziti = $dogovor_clientrekviziti.$dogovor_name."<BR>";
			$dogovor_clientrekviziti = $dogovor_clientrekviziti."Тел. ".$profile->phone."<BR>";
			$dogovor_clientrekviziti = $dogovor_clientrekviziti.$address."<BR>";
			if ($bankrekviziti) { $dogovor_clientrekviziti = $dogovor_clientrekviziti.$bankrekviziti."<BR>"; }
			$dogovor_clientrekviziti = $dogovor_clientrekviziti."<B>email:</b> ".$user->email."<BR>";

			$dogovor_number = $order->id;
			$dogovor_dd = $orderDate[2];
			$dogovor_mm = $orderDate[1];
			$dogovor_yyyy = $orderDate[0];

			$dogovor_site=GetSetting('company_url');
			$dogovor_billing=GetSetting('billing_url');

			$kvtpl = str_replace("{logo}",$dogovor_logo,$kvtpl);
			$kvtpl = str_replace("{number}",$dogovor_number,$kvtpl);
			$kvtpl = str_replace("{gorod}",$dogovor_gorod,$kvtpl);
			$kvtpl = str_replace("{dd}",$dogovor_dd,$kvtpl);
			$kvtpl = str_replace("{mm}",$dogovor_mm,$kvtpl);
			$kvtpl = str_replace("{yyyy}",$dogovor_yyyy,$kvtpl);
			$kvtpl = str_replace("{prodavec}",$dogovor_prodavec,$kvtpl);
			$kvtpl = str_replace("{svidetelstvo}",$dogovor_svidetelstvo,$kvtpl);
			$kvtpl = str_replace("{zakazchik}",$dogovor_zakazchik,$kvtpl);
			$kvtpl = str_replace("{site}",$dogovor_site,$kvtpl);
			$kvtpl = str_replace("{billing}",$dogovor_billing,$kvtpl);
			$kvtpl = str_replace("{terms}",$dogovor_terms,$kvtpl);
			$kvtpl = str_replace("{p_rekviziti}",$dogovor_rekviziti,$kvtpl);
			$kvtpl = str_replace("{email}",$dogovor_email,$kvtpl);
			$kvtpl = str_replace("{z_rekviziti}",$dogovor_clientrekviziti,$kvtpl);
			$kvtpl = str_replace("{shtamp}",$dogovor_shtamp,$kvtpl);

			$kvtpl = str_replace("{tarif}",$dogovor_tarif,$kvtpl);
			$kvtpl = str_replace("{hosttodate}",$dogovor_hosttodate,$kvtpl);
			$kvtpl = str_replace("{domain}",$dogovor_domain,$kvtpl);
			$kvtpl = str_replace("{domaintodate}",$dogovor_domaintodate,$kvtpl);
			$kvtpl = str_replace("{shopitem}",$dogovor_shopitem,$kvtpl);
			$kvtpl = str_replace("{shopitemtodate}",$dogovor_shopitemtodate,$kvtpl);

			?>
			<html>
			<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			</head>
			<body>
			<?

			print $kvtpl;

			?>
			</body>
			</html>
			<?
		}

		mclose();
		exit;
	}

	if ($sub == "orderaddons" and $id and is_array($selectedAddons)) {
		$order = @mysql_query("select *,TO_DAYS(todate)-TO_DAYS(NOW()) as leftdays from orders where id='$id' and uid='".$_SESSION["userId"]."' and archived=0") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		if (mysql_num_rows($order) > 0) {
			$order = mysql_fetch_object($order);
			$tarif = GetTarifById($order->tarif);

			$hostmonths=ceil($order->leftdays / 31);

			while (list($k,$v) = @each($selectedAddons)) {
				if ($v) {
					$selectedAddon = GetAddonById($v);

					if ($selectedAddon->isOs or $selectedAddon->isPanel) { $addonsInputCnt[$v] = 1; }
					if ($selectedAddonsInputCnt[$v] < 0) { $addonsInputCnt[$v] = -1 * $addonsInputCnt[$v]; }

					if ($selectedAddon->cntforoneorder and ($addonsInputCnt[$v]+GetOneAddonsCntForOrderId($order->id,$selectedAddon->id)) > $selectedAddon->cntforoneorder) { $addonsCntError = $_lang[OrderErrorAddonsCntWrong]." ".$selectedAddon->name.": ".$selectedAddon->cntforoneorder." ".$_lang[FakturaSokraschenieShtuka]; break; }

				}
			}

			if ($addonsCntError) { $error = $addonsCntError; $sub = "addons"; }
			else {
				$addonsCost=0;
				$addonsToSave="";
				$addonsToSaveText="";
				reset($selectedAddons);
				while (list($k,$v) = @each($selectedAddons)) {
					if ($v) {
						$selectedAddon = GetAddonById($v);
						if ($selectedAddon->id) {
							if ($addonsInputCnt[$v] < 0) { $addonsInputCnt[$v] = -1 * $addonsInputCnt[$v]; }

							if ($addonsToSaveText) {
								$addonsToSaveText = $addonsToSaveText.", ".$selectedAddon->name." - ".intval($addonsInputCnt[$v])." ".$_lang[FakturaSokraschenieShtuka];
							} else {
								$addonsToSaveText = $selectedAddon->name." - ".intval($addonsInputCnt[$v])." ".$_lang[FakturaSokraschenieShtuka];
							}

							$zz=0;
							while ($zz < intval($addonsInputCnt[$v])) {
								$zz++;
								$addonsToSave = $addonsToSave.":x:$selectedAddon->id";
							}

							#Устанавливаем спец. цену, если она указана для данной доп. услуги и данного пользователя
							#
							$addonSpecCost = GetSpecialCost($_SESSION['userId'],"addon",$selectedAddon->id);
							if ($addonSpecCost) {
								$addonsCost += $addonSpecCost["cost1"]*intval($addonsInputCnt[$v]);
								$addonsCost += $addonSpecCost["cost2"]*$hostmonths*intval($addonsInputCnt[$v]);
							} else {
								$selectedAddon->cost_start = $selectedAddon->cost_start / GetCurrencyKoeficientByCode($selectedAddon->cost_startCurrency);
								$selectedAddon->cost_monthly = $selectedAddon->cost_monthly / GetCurrencyKoeficientByCode($selectedAddon->cost_monthlyCurrency);

								$addonsCost += $selectedAddon->cost_start*intval($addonsInputCnt[$v]);
								$addonsCost += $selectedAddon->cost_monthly*$hostmonths*intval($addonsInputCnt[$v]);
							}
						}
					}
				}
				$addonsCost=round($addonsCost,2);

				$history = "Тариф: <B>$tarif->name</B>, заказ доп. услуг: $addonsToSaveText";

				@mysql_query("insert into bills (uid,tarif,host_id,money_addons,created,newaddons,history) values('".$_SESSION['userId']."','$tarif->id','$order->id','$addonsCost',NOW(),'$addonsToSave','$history')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				$bill_id=mysql_insert_id();
				$sid=sprintf("%04d", $bill_id);

				$orderTxt = "order: #$order->id, $tarif->name";
				if ($order->domain) {$orderTxt = $orderTxt.", $order->domain";}

			     	addUserLog($_SESSION['userId'],'orderAddons',"$addonsToSaveText [$orderTxt]");

				$tpl=GetTpl('email_touser_addons_order', $_SESSION["userLang"]);
				$subject = $tpl[subject]; $template = $tpl[template];

				if ($subject and $template) {
					$attachPDFtoBill = GetSetting("attachPDFtoBill");
					if (($attachPDFtoBill and $_SESSION["userAttachPDFtoBill"] != "2") or (!$attachPDFtoBill and $_SESSION["userAttachPDFtoBill"] == "1")) {
						$profile=GetUserProfileByUserId($_SESSION['userId']);

						if ($profile->org == "3" and $profile->firma and $profile->phone) {
							$attachFile = createFaktura('', $bill_id, 2);
						} else if ($profile->org == "2" and $profile->name and $profile->surname and $profile->phone) {
							$attachFile = createFaktura('', $bill_id, 2);
						} else if ($profile->org == "1") {
							$attachFile = createKvitanciya('', $bill_id, 2);

							if ($profile->name and $profile->surname and $profile->phone) {
								$attachFile2 = createFaktura('', $bill_id, 2);
							}
						}
						if (!$attachFile) {$attachFile="";}
						if (!$attachFile2) {$attachFile2="";}
					}

					$company_name=GetSetting('company_name');
					$company_url=GetSetting('company_url');
					$billing_url=GetSetting('billing_url');
					$support_url=GetSetting('support_url');
					$manager_email=GetSetting('manager_email');

					$tl=$_SESSION["userLogin"]; $tp='******';

				     	$template = str_replace('{company_name}',$company_name,$template);
				     	$template = str_replace('{company_url}',$company_url,$template);
				     	$template = str_replace('{billing_url}',$billing_url,$template);
				     	$template = str_replace('{support_url}',$support_url,$template);
				     	$template = str_replace('{tarif}',$tarif->name,$template);
				     	$template = str_replace('{domain}',$order->domain,$template);
				     	$template = str_replace('{addons}',$addonsToSaveText,$template);
				     	$template = str_replace('{login}',$tl,$template);
				     	$template = str_replace('{password}',$tp,$template);
				     	$template = str_replace('{schet}',$sid,$template);
				     	$template = str_replace('{addonscost}',round($addonsCost*CURK,2)." ".CURS,$template);
				     	$template = str_replace('{cost}',round($addonsCost*CURK,2)." ".CURS,$template);
				     	$template = str_replace('{userid}',$_SESSION['userId'],$template);
			     		
					WriteMailLog($subject,$template,$_SESSION["userId"]);
					sendmail($_SESSION["userEmail"],$company_name,$manager_email,$subject,$template,$attachFile,$attachFile2,$tpl[type]);
					sendmail($_SESSION["userEmail2"],$company_name,$manager_email,$subject,$template,$attachFile,$attachFile2,$tpl[type]);

					$admEmails=GetAdminEmailsWhereTrueParam("sendneworder");
					if (count($admEmail) > 0) {
						WriteMailLog("Duplicate: ".$subject,$template);
					}
					while (list($i,$em) = @each($admEmails)) {
						sendmail($em,'',$manager_email,"Duplicate: ".$subject,$template,$attachFile,$attachFile2,$tpl[type]);
					}

					@unlink($attachFile);
					@unlink($attachFile2);
				}

				Header("Location: billing.php?do=pay&fromreg=1&id=$bill_id");
				exit;
			}
		}
	}

	head('utf-8',$_lang[OrdersTitle]);
	print "<H1 class=pagetitle>".$_lang[OrdersTitle]."</H1><hr class=hr>";

	$r=@mysql_query("select * from bills where archived=0 and status = '0' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
	if (mysql_num_rows($r) > 0) {
		print "<font color=red>".$_lang[BillsNeOplachenoSchetov].": ".mysql_num_rows($r).". ".$_lang[BillsGoto]." <A class=rootlink href=?do=bills>".$_lang[BillsTitle]."</a> ".$_lang[BillGotoFor].".</font><BR><BR>";
	}

	if ($sub == 'delete' and $id) {
		$order = GetOrderById($id);
		if ($order->startdate == "0000-00-00" and $order->uid == $_SESSION["userId"] and (!$order->testPeriod or ($order->testPeriod and !$order->serverlogin))) {
			if (!$order->status) {
				@mysql_query("delete from orders where id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			} else {
				@mysql_query("update orders set archived=1 where id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			}
			@mysql_query("delete from orders_domains where host_id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

			$bs = @mysql_query("select * from bills where host_id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			while ($b = @mysql_fetch_object($bs)) {
				if (!$b->status) {
					@mysql_query("delete from bills where id='$b->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				} else {
					@mysql_query("update bills set archived=1 where id='$b->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				}
			}

			print $_lang[OrdersDeleteSuccess]."<BR><BR>";
		} else {
			print "<font color=red>".$_lang[OrdersErrorCantDelete]."</font><BR><BR>";
		}
	}

	if ($sub == 'restart' and $id) {
		$order = GetOrderById($id);
		if ($order->status == "1" and $order->uid == $_SESSION["userId"]) {
			if (restartUser($order->id, 1)) {
				print $_lang[OrdersRestartSuccess]."<BR><BR>";
			} else {
				print "<font color=red>".$_lang[OrdersRestartError]."</font><BR><BR>";
			}
		} else {
			print "<font color=red>".$_lang[OrdersErrorCantRestart]."</font><BR><BR>";
		}
	}

	if ($sub == 'reinstall' and $id) {
		$order = GetOrderById($id);
		if ($order->status == "1" and $order->uid == $_SESSION["userId"]) {
			if (reinstallUser($order->id, 1)) {
				print $_lang[OrdersReinstallSuccess]."<BR><BR>";
			} else {
				print "<font color=red>".$_lang[OrdersReinstallError]."</font><BR><BR>";
			}
		} else {
			print "<font color=red>".$_lang[OrdersErrorCantReinstall]."</font><BR><BR>";
		}
	}

	if ($sub == "addons" and $id) {
		if ($error) { print "<font color=red>".$error."</font><BR><BR>"; }

		$order = GetOrderById($id);
		if ($order->id and $order->uid == $_SESSION["userId"]) {
			$tarif = GetTarifById($order->tarif);

			$orderTxt = "$tarif->name";
			if ($order->domain) {$orderTxt = $orderTxt.", $order->domain";}

			print "<form action=billing.php method=post>";
			print "<input type=hidden name=do value=$do>";
			print "<input type=hidden name=sub value=orderaddons>";
			print "<input type=hidden name=id value=$id>";
		        print "<table class='rpTable' cellpadding=3>";
	       		print "<tr><td colspan=2 align=center class='$font_head'><B>".$_lang[OrdersAddonsForOrder]." ID #$order->id ($orderTxt)</b></td></tr>";
			print "<tr><Td>"; 
			$orderAddons = GetAddonsIdsForOrderId($order->id);
			$noGroups = array();
                        if (is_array($orderAddons)) {
                                while (list($k,$v) = each($orderAddons)) {
                                        if ($v) {
                                                $oneAddon=GetAddonById($v);
						if ($oneAddon->id) {
	                                                print "<input class=input type=checkbox value=$oneAddon->id checked disabled> $oneAddon->name<BR>";

							$group = GetAddonsGroupById($oneAddon->addonsgroup);
							if ($group->id and $group->isSelect and !$oneAddon->isOs and !$oneAddon->isPanel) {
								if ($oneAddon->cntforoneorder and GetOneAddonsCntForOrderId($order->id,$oneAddon->id) >= $oneAddon->cntforoneorder) {
									$noGroups[] = $group->id;
								}
							}
						}
                                        }
                                }
                        } else {
				print "<center>".$_lang[OrdersNoAddons]."</center>";
			}
			print "</td></tr>";
	       		print "<tr><td colspan=2 align=center class='$font_head'><B>".$_lang[OrdersAddonsOrder]."</b></td></tr>";
			print "<tr><Td>"; 
			$addonsGroupSelect = array();
			$orderAddonsAdd = GetAccessibleAddonsIdsForOrderId($order->id);

                        if (is_array($orderAddonsAdd)) {
                                while (list($k,$v) = each($orderAddonsAdd)) {
                                        if ($v) {
                                                $oneAddon=GetAddonById($v);
						if ($oneAddon->active and !$oneAddon->isOs and !$oneAddon->isPanel) {
							$group = GetAddonsGroupById($oneAddon->addonsgroup);
							if ($group->isSelect) { $addonsGroupSelect[$group->id][] = $oneAddon->id; }
							else {
								$oneAddon->cost_start = $oneAddon->cost_start / GetCurrencyKoeficientByCode($oneAddon->cost_startCurrency);
								$oneAddon->cost_monthly = $oneAddon->cost_monthly / GetCurrencyKoeficientByCode($oneAddon->cost_monthlyCurrency);

								$addonMoney = "";
								if ($oneAddon->cost_start) {
									$addonMoney=round($oneAddon->cost_start*CURK,2)." ".CURS." ".$_lang[OrderRazovo];
									if ($oneAddon->cost_monthly) {$addonMoney = $addonMoney." + ";}
								}
								if ($oneAddon->cost_monthly) {$addonMoney=$addonMoney.round($oneAddon->cost_monthly*CURK,2)." ".CURS."/".$_lang[OrderSokraschenieMonth];}
								if (!$addonMoney) { $addonMoney = $_lang[OrderFree]; }

		                                                print "<input class=input type=checkbox name=selectedAddons[] value=$oneAddon->id> $oneAddon->name ($addonMoney)";

								if (!$addonsInputCnt[$v]) { $addonsInputCnt[$v] = 1; }

								if ((!$oneAddon->cntforoneorder or ($oneAddon->cntforoneorder != 1 and GetOneAddonsCntForOrderId($order->id,$oneAddon->id) < $oneAddon->cntforoneorder)) and $oneAddon->allowSetCnt) { print ", <input type=text class=input size=1 name=addonsInputCnt[$v] value=".$addonsInputCnt[$v]."> ".$_lang[FakturaSokraschenieShtuka];} else { print "<input type=hidden name=addonsInputCnt[$v] value=".$addonsInputCnt[$v].">"; }

								print "<BR>";
							}
						}
                                        }
                                }


				@ksort($addonsGroupSelect);
				while (list($k,$v) = @each($addonsGroupSelect)) {
					if ($k and !@in_array($k,$noGroups)) {
						$f=0;
						$fCNT = count($v);
						while (list($kk,$vv) = @each($v)) {
							$f++;

							$oneAddon = GetAddonById($vv);
							$group = GetAddonsGroupById($k);

							$oneAddon->cost_start = $oneAddon->cost_start / GetCurrencyKoeficientByCode($oneAddon->cost_startCurrency);
							$oneAddon->cost_monthly = $oneAddon->cost_monthly / GetCurrencyKoeficientByCode($oneAddon->cost_monthlyCurrency);

							$addonMoney = "";
							if ($oneAddon->cost_start) {
								$addonMoney=round($oneAddon->cost_start*CURK,2)." ".CURS." ".$_lang[OrderRazovo];
								if ($oneAddon->cost_monthly) {$addonMoney = $addonMoney." + ";}
							}
							if ($oneAddon->cost_monthly) {$addonMoney=$addonMoney.round($oneAddon->cost_monthly*CURK,2)." ".CURS."/".$_lang[OrderSokraschenieMonth];}
							if (!$addonMoney) { $addonMoney = $_lang[OrderFree]; }

							if ($f == 1) {
								print "</td></tr>";
						       		print "<tr><td colspan=2 align=center class='$font_head'><B>".$_lang[OrdersAddonsOrder]." ($group->name)</b></td></tr>";
								print "<tr><Td>"; 
								print "<select class=input name=selectedAddons[] ><option></option>";
							}

	                                                print "<option value=$oneAddon->id> $oneAddon->name ($addonMoney)";

							if (!$addonsInputCnt[$vv]) { $addonsInputCnt[$vv] = 1; }

							print "<BR>";

							if ($f == $fCNT) {
								print "</select>";

								reset($v);
								while (list($kk,$vv) = @each($v)) {
									print "<input type=hidden name=addonsInputCnt[$vv] value=".$addonsInputCnt[$vv].">";
								}
							}
						}
					}
				}


                        } else {
				print "<center>".$_lang[OrdersNoAvailAddons]."</center>";
			}
			print "</td></tr>";
	       		print "<tr><td colspan=2 align=center class='$font_head'><input class=button type=submit value='".$_lang[Order]."'></td></tr>";
			print "</table>";
			print "</form>";
		} else { print $_lang[OrdersErrorNoOrder]."<BR><BR>"; }
	}

	if ($sub == "changepassword2" and $id and GetSetting("enableChangeAccountPassword")) {
		$order = GetOrderById($id);
		if ($order->id and $order->uid == $_SESSION["userId"]) {
			if (!$newPassword) { print $_lang[ErrorNoPassword]."<BR><BR>";}
			else {
				if (changePassword($order->id, $newPassword)) {
		
					print $_lang[OrdersChangePasswordSuccess]."<BR><BR>";
					$sub = "";

				} else {
					print $_lang[OrdersChangePasswordError]."<BR><BR>";
				}
			}
		} else { print $_lang[OrdersErrorNoOrder]."<BR><BR>"; }
	}

	if (($sub == "changepassword" or $sub == "changepassword2") and $id and GetSetting("enableChangeAccountPassword")) {
		$order = GetOrderById($id);
		if ($order->id and $order->uid == $_SESSION["userId"]) {
			$tarif = GetTarifById($order->tarif);

			$orderTxt = "$tarif->name";
			if ($order->domain) {$orderTxt = $orderTxt.", $order->domain";}

			print "<form action=billing.php method=post>";
			print "<input type=hidden name=do value=$do>";
			print "<input type=hidden name=sub value=changepassword2>";
			print "<input type=hidden name=id value=$id>";
		        print "<table class='rpTable' cellpadding=3>";
	       		print "<tr><td colspan=2 align=center class='$font_head'><B>".$_lang[OrdersChangePasswordTitle]." ID #$order->id ($orderTxt)</b></td></tr>";
			print "<tr><Td align=center>".$_lang[ProfileNewPassword].": "; 
			print "<input type=password class=input name=newPassword size=15 value=''></td></tr>";
	       		print "<tr><td colspan=2 align=center class='$font_head'><input class=button type=submit value='".$_lang[Change]."'></td></tr>";
			print "</table>";
			print "</form>";
		} else { print $_lang[OrdersErrorNoOrder]."<BR><BR>"; }
	}

	getfont();

	if ($group != "") {
		$group = intval($group);
		$whereAddon = " and tarifsgroup='$group'";
	}

        $r=@mysql_query("select o.*,t.tarifsgroup from orders as o, tarifs as t WHERE o.uid='".$_SESSION["userId"]."' and o.archived=0 and t.id=o.tarif $whereAddon order by o.id desc") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
        $rows = mysql_num_rows($r);
        list($start, $perPage, $txt) = MakePages($page, $rows);

	?>
	<table class='rpTable' cellpadding=3>
        <tr><td colspan=9 align=right><? print $txt?></td></tr>
	<tr class='<? print $font_head?>' align=center><td>ID</td><Td><? print $_lang[OrdersDate]?></td><td><? print $_lang[OrdersDomain]?></td><td><? print $_lang[OrdersTarif]?></td><td><? print $_lang[OrdersEnd]?></td><td><? print $_lang[OrdersLeftDays]?></td><td><? print $_lang[BillsStatus]?></td><td></td></tr>
	<?
	$r=@mysql_query("select o.*,TO_DAYS(o.todate)-TO_DAYS(NOW()) as leftdays,t.tarifsgroup from orders as o, tarifs as t where o.uid='".$_SESSION["userId"]."' and o.archived=0 and t.id=o.tarif $whereAddon order by o.id desc LIMIT $start,$perPage") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
	$cnt=0;
	getfont();
	while ($rr = @mysql_fetch_object($r)) {
		getfont();
		$t=mysql_query("select * from tarifs where id = '$rr->tarif'");
		$t=mysql_fetch_object($t);
		$b=mysql_query("select * from bills where archived=0 and host_id = '$rr->id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		$bills=mysql_num_rows($b);
		$b=mysql_fetch_object($b);
		$bp=mysql_query("select * from bills where archived=0 and host_id = '$rr->id' and uid='".$_SESSION["userId"]."' and !(status='0')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		$billspayed=mysql_num_rows($bp);
		$billsNonPayed = $bills-$billspayed;
		$server = GetServers($rr->serverid);

		$tVid = $t->vid;
		if ($rr->testPeriod) {
			$tVid = $_lang[OrderTestPeriod];
		} else {
			$tVid = $_lang[OrderType][$tVid];
		}

	        if ($rr->leftdays != "") {
			if ($rr->status == "1") {
				if ($rr->leftdays <= 10 and $rr->startdate != "0000-00-00") {
					$leftDays = "<font color=red>".$rr->leftdays."</font>"; 
				} else {
					$leftDays = $rr->leftdays; 
				}
			} else { $leftDays = "-"; }

			$leftDays="<img src=./_rootimages/hosting.gif border=0 alt='".$tVid."'> ".$leftDays; 
		}
		else { $leftDays = "-"; }

        	if ($rr->startdate != "0000-00-00" or $rr->testPeriod) { $todate="<img src=./_rootimages/hosting.gif border=0 alt='".$tVid."'> ".mydate($rr->todate); }
	        else { $todate = "-"; }
		
        	if ($rr->domain_reg == "1" or $rr->domain_reg == "3") {
			$d=@mysql_query("select *,TO_DAYS(todate)-TO_DAYS(NOW()) as leftdays from orders_domains where host_id='$rr->id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			$d=mysql_fetch_object($d);

			if ($d->leftdays == "") { $d->leftdays = "-"; }
			else if ($d->leftdays <= 30 and $d->startdate != "0000-00-00") { $d->leftdays = "<font color=red>".$d->leftdays."</font>"; }
			if ($d->leftdays != "-") { $leftDays .= " <img src=./_rootimages/domain.gif border=0 alt='".$_lang[Domain]."'> ".$d->leftdays; }
			
			if ($d->startdate != "0000-00-00") { $todate .= "<BR><img src=./_rootimages/domain.gif border=0 alt='".$_lang[Domain]."'> ".mydate($d->todate); }
		}

		if ($billspayed > 0 or $rr->startdate != "0000-00-00" or ($rr->testPeriod and $rr->serverlogin)) {
			$delete='';
		} else {
			$delete="<A class=rootlink href=?do=$do&sub=delete&id=$rr->id onclick=\"javascript: return confirm('".$_lang[OrdersDeleteAlert]."');\"><img src=./_rootimages/del.gif style='padding-right: 5px' border=0 alt='".$_lang[OrdersDeleteOrder]."'>$_lang[OrdersDeleteOrder]</a><BR>";
		}
		if ($billsNonPayed == 0 and $rr->todate != "0000-00-00" and !$rr->testPeriod) {
			$renew="<a class=rootlink href=?do=renew&host_id=$rr->id><img src=./_rootimages/renew.gif style='padding-right: 5px' border=0 alt='".$_lang[OrdersRenewOrder]."'>$_lang[OrdersRenewOrder]</a><BR>";
		} else {
			$renew='';
		}

		if ($rr->filename) {
			$download="<a class=rootlink href=?do=download&id=$rr->id><img src=./_rootimages/download.gif style='padding-right: 5px' border=0 alt='".$_lang[OrdersDownloadFile]."'>$_lang[OrdersDownloadFile]</a><BR>";
		} else {
			$download='';
		}

		if (GetSetting("dogovor_enable")) {
			$dogovor="<a class=rootlink href='' onClick=\"popupWin = window.open('billing.php?do=$do&host_id=$rr->id&sub=dogovor', 'dogovor', 'location,width=650,height=600,top=0,scrollbars=yes'); popupWin2 = window.open('billing.php?do=$do&host_id=$rr->id&sub=dodatok', 'dodatok', 'location,width=650,height=600,top=20,left=20,scrollbars=yes'); popupWin.focus(); return false;\"><img src=./_rootimages/dogovor.gif style='padding-right: 5px' border=0 alt='".$_lang[OrdersPrintDogovor]."'>$_lang[OrdersPrintDogovor]</a><BR>";
		} else {
			$dogovor="";
		}

		if ($rr->remarkUser or $rr->comment) {
			$rr->remarkUser = preg_replace("/\n/ui","<BR>",$rr->remarkUser."<BR>".$rr->comment);
			$remark="<span title='<B>".$_lang[OrderRemark].":</b><BR>$rr->remarkUser'><img src=./_rootimages/question2.gif style='padding-right: 5px' border=0>$_lang[OrderRemark]</span><BR>";
		} else {
			$remark="";
		}

		if ((($server->type == "shellscript" and $t->scriptRestart) or ($server->type == "hypervm" or $server->type == "hypervmxen" or $server->type == "vdsmanager" or $server->type == "solusvmopenvz" or $server->type == "solusvmxen" or $server->type == "solusvmxenhvm" or $server->type == "solusvmkvm" or ($server->type == "rootpanel" and ($t->vid == "vds" or $t->vid == "dedicated")))) and $rr->status == "1") {
			$restart="<a class=rootlink href=?do=$do&sub=restart&id=$rr->id onclick=\"javascript: return confirm('".$_lang[OrdersRestartAlert]."');\"><img src=./_rootimages/restart.gif style='padding-right: 5px' border=0 alt='".$_lang[OrdersRestart]."'>$_lang[OrdersRestart]</a><BR>";
		} else {
			$restart="";
		}

		if ((($server->type == "shellscript" and $t->scriptReinstall) or ($server->type == "vdsmanager" or $server->type == "solusvmopenvz" or $server->type == "solusvmxen" or $server->type == "solusvmxenhvm" or $server->type == "solusvmkvm" or ($server->type == "rootpanel" and ($t->vid == "vds" or $t->vid == "dedicated")))) and $rr->status == "1") {
			$reinstall="<a class=rootlink href=?do=$do&sub=reinstall&id=$rr->id onclick=\"javascript: return confirm('".$_lang[OrdersReinstallAlert]."');\"><img src=./_rootimages/reinstall.gif style='padding-right: 5px' border=0 alt='".$_lang[OrdersReinstall]."'>$_lang[OrdersReinstall]</a><BR>";
		} else {
			$reinstall="";
		}

		if (($rr->status == "1" or ($rr->testPeriod and $rr->serverlogin and $rr->serverpassword)) and GetSetting("enableGotoAccount") and ($server->type == "isp" or $server->type == "da" or $server->type == "cpanel" or $server->type == "plesk" or $server->type == "plesk10" or $server->type == "hypervm" or $server->type == "hypervmxen" or $server->type == "vdsmanager" or $server->type == "gamecp") and $rr->serverlogin and $rr->serverpassword) {
			$gotoAcc = "<a class=rootlink href=?do=gotoAccount&id=$rr->id target=_blank><img src=./_rootimages/gotoclient.gif style='padding-right: 5px' border=0 alt='".$_lang[OrdersGotoAccount]."'>$_lang[OrdersGotoAccount]</a><BR>";
		} else {
			$gotoAcc = "";
		}

		if ($rr->status == "1" and GetSetting("enableChangeAccountPassword") and ($server->type == "isp" or $server->type == "da" or $server->type == "cpanel" or $server->type == "plesk" or $server->type == "plesk10" or $server->type == "shellscript" or $server->type == "solusvmopenvz" or $server->type == "solusvmxen" or $server->type == "solusvmxenhvm" or $server->type == "solusvmkvm") and $rr->serverlogin) {
			$changePassword = "<a class=rootlink href=?do=$do&sub=changepassword&id=$rr->id><img src=./_rootimages/changepassword.gif style='padding-right: 5px' border=0 alt='".$_lang[OrdersChangePassword]."'>$_lang[OrdersChangePassword]</a><BR>";
		} else {
			$changePassword = "";
		}

		if ($rr->serverlogin) {
			$loginTxt = "<BR>[".$_lang[OrdersLogin].": ".$rr->serverlogin."]";
		} else {
			$loginTxt = "";
		}

                $statusHosting="<img src=./_rootimages/obrabotan_".$rr->status."_small.gif border=0 alt='".$_status[$rr->status]."' title='".$_status[$rr->status]."'>";
		if ($rr->testPeriod) { $statusHosting = $statusHosting." <img src=./_rootimages/test_small.gif border=0 alt='".$_lang[OrdersStatusTest]."'>"; }

		$orderAddons = mb_split(":x:", $rr->addons);
		sort($orderAddons);
		$lastaddon='';
		$orderAddonsTxt='';
		while (list($k,$v) = @each($orderAddons)) {
			if ($v) {
				$oneAddon=GetAddonById($v);
				if ($oneAddon->id) {
					if ($lastaddon) {$orderAddonsTxt .= "<BR>";}
					$orderAddonsTxt .= "- $oneAddon->name";
					$lastaddon=$oneAddon->textid;
				}
			}
		}

		if ($rr->leftdays >= 0 and $rr->startdate != "0000-00-00") {
			if (!$orderAddonsTxt) {$orderAddonsTxt=$_lang[OrdersAddonOrder];}
			$addons="<a class=rootlink href=?do=orders&sub=addons&id=$rr->id title='<B>".$_lang[OrderAddons].":</b><BR>$orderAddonsTxt'><img src=./_rootimages/addons2.gif style='padding-right: 5px' border=0>$_lang[OrderAddons]</a><BR>";
		} else if ($orderAddonsTxt) {
			$addons="<span title='<B>".$_lang[OrderAddons].":</b><BR>$orderAddonsTxt'><img src=./_rootimages/addons2.gif border=0> $_lang[OrderAddons]</span><BR>";
		} else {
			$addons="";
		}

		if ($billsNonPayed == 0 and GetAccessibleChangeTarifsForHostingOrder($rr->id)) { 
			$changetarif = "<BR>[<A class=rootlink href=?do=changetarif&host_id=$rr->id>".$_lang[OrdersChangeTarif]."</a>]"; 
		} else { 
			$changetarif=""; 
		}

		if ($billsNonPayed == 0 and $server->type == "ventrilols" and GetAccessibleChangeServersForOrder($rr->id)) { 
			$changeserver = "<BR>[".$_lang[OrdersServer].": $server->place]<BR>[<A class=rootlink href=?do=changeserver&host_id=$rr->id>".$_lang[OrdersChangeServer]."</a>]"; 
		} else { 
			$changeserver=""; 
		}

		if ($billsNonPayed == 0 and $rr->status == "1" and $t->enableSlots) {
			$changeslots = "<BR>[".$_lang[OrdersSlots].": $rr->slots]<BR>[<A class=rootlink href=?do=changeslots&host_id=$rr->id>".$_lang[OrdersChangeSlots]."</a>]"; 
		} else {
			$changeslots = "";
		}

		if ($rr->domain) {$domainTxt = "<B>$rr->domain</b><BR>";}
		else { $domainTxt = ""; }

		$orderIPs = GetServersIPs($server->id,$rr->id);
		$orderIPs = @mysql_num_rows($orderIPs);
		if ($orderIPs) { $orderIPs = "<BR>".$_lang[OrdersOrderIPsCount].": $orderIPs";} else { $orderIPs = "";}

		print "
		<tr class='$font_row' height=30>
		<td align=center>$rr->id</td>
		<td align=center>".mydate($rr->orderdate)."</td>
		<td>".$domainTxt."[".$_newregmin[$rr->domain_reg]."]</td>
		<td align=center><b>$t->name</b>$changetarif$loginTxt$changeserver$orderIPs$changeslots</td>
		<td align=center>$todate</td>
		<td align=center nowrap>$leftDays</td>
		<td align=center>$statusHosting</td>
		<td align=left valign=middle>$remark$dogovor$download$gotoAcc$changePassword$restart$reinstall$addons<A class=rootlink href=?do=bills&param=host_id&search=$rr->id><img src=./_rootimages/bills.gif style='padding-right: 5px' border=0 alt='".$_lang[BillsTitle].": $bills'>$_lang[BillsTitle]: $bills</a><BR>$renew$delete</td>
		</tr>
		";

		$cnt++;
	}
	?>
        <tr class=<? print $font_head?>><Td colspan=9><? print $_lang[OrdersTotalOrders]?>: <? print $rows?>, <? print $_lang[OrdersOrdersOnPage]?>: <? print $cnt?></td></tr>
        <tr><td colspan=9 align=right><? print $txt?></td></tr>
	</table>
	<?
	foot('utf-8');
}

if ($do == "domains") {
	if ($sub == "updateprivacy" and $id) {
		$domain = GetDomainById($id);
		$zone = GetZoneByDomainOrderId($domain->id);

		if ($domain->uid == $_SESSION["userId"] and ($zone->privacy or $domain->privacy)) {
			$profile = GetUserProfileByUserId($_SESSION['userId'],$domain->profileId);

			if ($zone->privacy == "person" and $profile->org == "3") { 
				$result = $_lang[Error].": ".$_lang[DomainPrivacyProfileError];
			} 
			else {
				if ($privacy != "0" and $privacy != "1") { $result = $_lang[DomainPrivacyChangeNoPrivacy]; $sub = "privacy"; }
				else {
					if ($zone->privacy_cost and ($zone->privacy_payafterdisable or !$domain->privacy or $domain->todateprivacy == "0000-00-00" or $domain->leftdaysprivacy < 0) and $privacy == "1") { 
						$history = "Домен: <B>$domain->domain</B>, заказ Privacy Protection";

						@mysql_query("insert into bills (uid,domain_id,money_domain,created,privacy,history) values('".$_SESSION['userId']."','$domain->id','$zone->privacy_cost',NOW(),'1','$history')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
						$bill_id=mysql_insert_id();

					     	addUserLog($_SESSION['userId'],'orderDomainAddons',"Privacy Protection для $domain->domain");

						Header("Location: billing.php?do=pay&id=$bill_id");exit;
					}
					else {
						if (!$privacy and $zone->privacy_payafterdisable) {
						        @mysql_query("update orders_domains set privacy='0' where id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
						}

						if (updateDomainPrivacy($domain->id, $privacy)) {
							$result = $_lang[DomainPrivacyChangeSuccess];
						}
						else {
							$result = $_lang[OrdersPrivacyChangeError];
						}
					}
				}
			}
		}
		else { $result = $_lang[ErrorBadId]; }
	}


        head('utf-8',$_lang[DomainsTitle]);
	print "<H1 class=pagetitle>".$_lang[DomainsTitle]."</H1><hr class=hr>";

        $r=@mysql_query("select * from bills where archived=0 and status = '0' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
        if (mysql_num_rows($r) > 0) {
                print "<font color=red>".$_lang[BillsNeOplachenoSchetov].": ".mysql_num_rows($r).". ".$_lang[BillsGoto]." <A class=rootlink href=?do=bills>".$_lang[BillsTitle]."</a> ".$_lang[BillGotoFor].".</font><BR><BR>";
        }

	if ($result) { print $result."<BR><BR>"; }

	if ($sub == "updatens" and $id) {
	        $d=@mysql_query("select * from orders_domains where id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
        	if (mysql_num_rows($d) > 0) {
			$d = @mysql_fetch_object($d);
			$zone = GetZoneById($d->zone_id);
			$registrator = GetRegistratorById($d->autoregby);

			if ($useDefaultNS) {
				$ns1 = $zone->defaultNS1;
				$ns2 = $zone->defaultNS2;
				$ns3 = $zone->defaultNS3;
				$ns4 = $zone->defaultNS4;
			}

			$ns1=trim(mb_strtolower($ns1)); $ns1 = preg_replace("/\.$/ui","",$ns1);
			$ns2=trim(mb_strtolower($ns2)); $ns2 = preg_replace("/\.$/ui","",$ns2);
			$ns3=trim(mb_strtolower($ns3)); $ns3 = preg_replace("/\.$/ui","",$ns3);
			$ns4=trim(mb_strtolower($ns4)); $ns4 = preg_replace("/\.$/ui","",$ns4);

			$ns1ip=trim($ns1ip); if (!preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/u",$ns1ip)) { $ns1ip = ""; }
			$ns2ip=trim($ns2ip); if (!preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/u",$ns2ip)) { $ns2ip = ""; }
			$ns3ip=trim($ns3ip); if (!preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/u",$ns3ip)) { $ns3ip = ""; } if (!$ns3) { $ns3ip = ""; }
			$ns4ip=trim($ns4ip); if (!preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/u",$ns4ip)) { $ns4ip = ""; } if (!$ns4) { $ns4ip = ""; }

			if ($d->lastDNSchangeTime) {
				$changeDNStimeout = GetSetting("changeDNStimeout");
				$minutes = (time() - $d->lastDNSchangeTime) / 60;
			}

			if ($minutes and $changeDNStimeout and $minutes <= $changeDNStimeout) {$error=$_lang[DomainsErrorDNSTimeout]." (".round($minutes,2)."/$changeDNStimeout)";}
			else if (!$ns1) {$error=$_lang[OrderErrorNoPrymaryNS];}
			else if (!preg_match("/^[a-z0-9]{1}[a-z0-9-]*\.[a-z0-9-.]*[a-z0-9]{1}$/u",$ns1)) {$error=$_lang[OrderErrorNoPrymaryNS];}
			else if (preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/u",$ns1)) {$error=$_lang[OrderErrorNoPrymaryNS];}
			else if (preg_match("/\.$d->domain$/u",$ns1) and !$ns1ip) {$error=$_lang[OrderErrorOwnNS1];}
			else if ($ns1ip and !preg_match("/\.$d->domain$/u",$ns1)) {$error=$_lang[OrderErrorOwnNSIP];}
			else if (!$ns2) {$error=$_lang[OrderErrorNoSecondaryNS];}
			else if (!preg_match("/^[a-z0-9]{1}[a-z0-9-]*\.[a-z0-9-.]*[a-z0-9]{1}$/u",$ns2)) {$error=$_lang[OrderErrorNoSecondaryNS];}
			else if (preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/u",$ns2)) {$error=$_lang[OrderErrorNoSecondaryNS];}
			else if (preg_match("/\.$d->domain$/u",$ns2) and !$ns2ip) {$error=$_lang[OrderErrorOwnNS2];}
			else if ($ns2ip and !preg_match("/\.$d->domain$/u",$ns2)) {$error=$_lang[OrderErrorOwnNSIP];}
			else if ($ns3 and !preg_match("/^[a-z0-9]{1}[a-z0-9-]*\.[a-z0-9-.]*[a-z0-9]{1}$/u",$ns3)) {$error=$_lang[OrderErrorNoThirdNS];}
			else if ($ns3 and preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/u",$ns3)) {$error=$_lang[OrderErrorNoThirdNS];}
			else if ($ns3 and preg_match("/\.$d->domain$/u",$ns3) and !$ns3ip) {$error=$_lang[OrderErrorOwnNS3];}
			else if ($ns3ip and !preg_match("/\.$d->domain$/u",$ns3)) {$error=$_lang[OrderErrorOwnNSIP];}
			else if ($ns4 and !preg_match("/^[a-z0-9]{1}[a-z0-9-]*\.[a-z0-9-.]*[a-z0-9]{1}$/u",$ns4)) {$error=$_lang[OrderErrorNoFourNS];}
			else if ($ns4 and preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/u",$ns4)) {$error=$_lang[OrderErrorNoFourNS];}
			else if ($ns4 and preg_match("/\.$d->domain$/u",$ns4) and !$ns4ip) {$error=$_lang[OrderErrorOwnNS4];}
			else if ($ns4ip and !preg_match("/\.$d->domain$/u",$ns4)) {$error=$_lang[OrderErrorOwnNSIP];}
			else if ($ns1 == $ns2) {$error=$_lang[OrderErrorNoSecondaryNS];}
			else if ($ns3 and ($ns3 == $ns1 or $ns3 == $ns2)) {$error=$_lang[OrderErrorNoThirdNS];}
			else if ($ns4 and ($ns4 == $ns1 or $ns4 == $ns2 or $ns4 == $ns3)) {$error=$_lang[OrderErrorNoFourNS];}
			else if ($ns2ip and $ns1ip == $ns2ip) {$error=$_lang[OrderErrorOwnNS2];}
			else if ($ns3ip and ($ns3ip == $ns1ip or $ns3ip == $ns2ip)) {$error=$_lang[OrderErrorOwnNS3];}
			else if ($ns4ip and ($ns4ip == $ns1ip or $ns4ip == $ns2ip or $ns4ip == $ns3ip)) {$error=$_lang[OrderErrorOwnNS4];}
			else if (!$useDefaultNS and $zone->enableCheckNS and !checkDNS($d->domain, $zone->id, $ns1, $ns2, $ns3, $ns4)) {$error=$GLOBALerror;}
			else {
				if ($d->autoregby and $registrator->type != "activeby") {
					if (updateNS($id,$ns1,$ns2,$ns3,$ns4,$ns1ip,$ns2ip,$ns3ip,$ns4ip,$oldns1,$oldns2,$oldns3,$oldns4,$oldns1ip,$oldns2ip,$oldns3ip,$oldns4ip,$useDefaultNS)) {
						@mysql_query("update orders_domains set lastDNSchangeTime=".time().",ns1='$ns1',ns2='$ns2',ns3='$ns3',ns4='$ns4',ns1ip='$ns1ip',ns2ip='$ns2ip',ns3ip='$ns3ip',ns4ip='$ns4ip' where id='$id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

						if (!$useDefaultNS and $d->dnsServerId) {
							deleteDomainZoneAtServer($id);
						}


						print $_lang[DomainsChangeDNSSuccess]."<BR><BR>";
					} else {
						print $_lang[DomainsErrorChangeDNS]." ".$GLOBALerror."<BR><BR>"; $sub = "ns";
					}
				} else {
					$nsss = "NS1: $ns1 $ns1ip\nNS2: $ns2 $ns2ip";
					if ($ns3) {$nsss = $nsss."\nNS3: $ns3 $ns3ip";}
					if ($ns4) {$nsss = $nsss."\nNS4: $ns4 $ns4ip";}

					$subject_msg = "�?зменение DNS-серверов для $d->domain";
					$message = "Пользователь ".$_SESSION["userLogin"]." заказал смену DNS-серверов для домена $d->domain (ID # $d->id) на:\n\n$nsss\n\nВам необходимо изменить DNS-сервера у регистратора и указать их в редактировании заказа на домен в биллинге.\n\n--\nRootPanel";

					$manager_email = GetSetting("manager_email");
					$admEmails=GetAdminEmailsWhereTrueParam("senddns");
					if (count($admEmails) > 0) {
						WriteMailLog($subject_msg,$message);
					}
					while (list($i,$em) = @each($admEmails)) {
						sendmail($em,'',$manager_email,$subject_msg,$message);
					}

					if (GetSetting("smsGateway")) {
						$smsmsg = "Nuzhno smenit DNS for $d->domain, user ".$_SESSION["userLogin"];

						$admIds=GetAdminIdsWhereTrueParam("sms_senddns");
						while (list($i,$aid) = @each($admIds)) {
							sendSMS('',$aid,$smsmsg);
						}
					}

					@mysql_query("update orders_domains set lastDNSchangeTime=".time()." where id='$id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

					print $_lang[DomainsChangeDNSSendSuccess]."<BR><BR>";
				}
			}

			if ($error) { print $error."<BR><BR>"; $sub = "ns"; }
		}
		else { print $_lang[ErrorBadId]."<BR><BR>"; }
	}

	if ($sub == "ns" and $id) {
		$domain = GetDomainById($id);
		$zone = GetZoneByDomainOrderId($domain->id);

		if ($domain->uid == $_SESSION["userId"]) {
			if ($domain->ns1) { $ns[0] = trim($domain->ns1); $nsip[0] = trim($domain->ns1ip); }
			if ($domain->ns2) { $ns[1] = trim($domain->ns2); $nsip[1] = trim($domain->ns2ip); }
			if ($domain->ns3) { $ns[2] = trim($domain->ns3); $nsip[2] = trim($domain->ns3ip); }
			if ($domain->ns4) { $ns[3] = trim($domain->ns4); $nsip[3] = trim($domain->ns4ip); }

			if ($domain->autoregby) {
				$registrator = GetRegistratorById($domain->autoregby);

				if (!$ns) { $ns = GetNS($id); }

				if (!$ns) { print $_lang[DomainsErrorCantGetDNS]." ".$GLOBALerror."<BR><BR>"; }
			}

			print "<form action=billing.php method=post>";
			print "<input type=hidden name=do value=$do>";
			print "<input type=hidden name=sub value=updatens>";
			print "<input type=hidden name=id value=$id>";
			print "<input type=hidden name=oldns1 value='$ns[0]'>";
			print "<input type=hidden name=oldns2 value='$ns[1]'>";
			print "<input type=hidden name=oldns3 value='$ns[2]'>";
			print "<input type=hidden name=oldns4 value='$ns[3]'>";
			print "<input type=hidden name=oldns1ip value='$nsip[0]'>";
			print "<input type=hidden name=oldns2ip value='$nsip[1]'>";
			print "<input type=hidden name=oldns3ip value='$nsip[2]'>";
			print "<input type=hidden name=oldns4ip value='$nsip[3]'>";
		        print "<table class='rpTable' cellpadding=3>";
	       		print "<tr><td colspan=2 align=center class=$font_head><B>".$_lang[DomainsDNSFor]." $domain->domain</b></td></tr>";
			if ($registrator->type == "ficora") { print "<tr><Td>".$_lang[AuthorizationKey].":</td><td><input class=input type=text size=30 name=authorization_key value='$authorization_key'></td></tr>"; }

			if ($zone->enableDefaultNS and $zone->defaultNS1 and $zone->defaultNS2) {
				if ($useDefaultNS) {
					$useDefaultNSCheck1 = "checked";
					$useDefaultNSCheck2 = "";
					$nsDisabled = "disabled";
					$ns1="";
					$ns2="";
					$ns3="";
					$ns4="";
					$ns1ip="";
					$ns2ip="";
					$ns3ip="";
					$ns4ip="";
				} else {
					$useDefaultNSCheck1 = "";
					$useDefaultNSCheck2 = "checked";
					$nsDisabled = "";
				}

				print "<tr><td colspan=2>";
				print "<input type=radio name=useDefaultNS $useDefaultNSCheck1 onclick='this.form.ns1.disabled=1; this.form.ns2.disabled=1; this.form.ns3.disabled=1; this.form.ns4.disabled=1;' value=1 class=input> ".$_lang[OrderUseDefaultNS]."<BR>";
				print "<input type=radio name=useDefaultNS $useDefaultNSCheck2 onclick='this.form.ns1.disabled=0; this.form.ns2.disabled=0; this.form.ns3.disabled=0; this.form.ns4.disabled=0;' value=0 class=input> ".$_lang[OrderFillOwnNS].":";
				print "</td></tr>";
			}

			print "<tr><Td>".$_lang[DomainsNS1].":</td><td><input class=input type=text size=30 name=ns1 value=\"$ns[0]\" "; if ($useDefaultNS) { print "disabled"; } print ">";
			if ($registrator->type != "gfx") { print " ".$_lang[DomainsIP].": <input class=input type=text size=30 name=ns1ip value=\"$nsip[0]\" "; if ($useDefaultNS) { print "disabled"; } print ">"; }
			print "</td></tr>";
			print "<tr><Td>".$_lang[DomainsNS2].":</td><td><input class=input type=text size=30 name=ns2 value=\"$ns[1]\" "; if ($useDefaultNS) { print "disabled"; } print ">";
			if ($registrator->type != "gfx") { print " ".$_lang[DomainsIP].": <input class=input type=text size=30 name=ns2ip value=\"$nsip[1]\" "; if ($useDefaultNS) { print "disabled"; } print ">"; }
			print "</td></tr>";
			print "<tr><Td>".$_lang[DomainsNS3].":</td><td><input class=input type=text size=30 name=ns3 value=\"$ns[2]\" "; if ($useDefaultNS) { print "disabled"; } print ">";
			if ($registrator->type != "gfx") { print " ".$_lang[DomainsIP].": <input class=input type=text size=30 name=ns3ip value=\"$nsip[2]\" "; if ($useDefaultNS) { print "disabled"; } print ">"; }
			print "</td></tr>";
			print "<tr><Td>".$_lang[DomainsNS4].":</td><td><input class=input type=text size=30 name=ns4 value=\"$ns[3]\" "; if ($useDefaultNS) { print "disabled"; } print ">";
			if ($registrator->type != "gfx") { print " ".$_lang[DomainsIP].": <input class=input type=text size=30 name=ns4ip value=\"$nsip[3]\" "; if ($useDefaultNS) { print "disabled"; } print ">"; }
			print "</td></tr>";
	       		print "<tr><td colspan=2 align=center class=$font_head><input class=button type=submit value='".$_lang[Change]."'></td></tr>";
			print "</table>";
			print "</form>";
		}
		else { print $_lang[ErrorBadId]."<BR><BR>"; }
	}

	if ($sub == "privacy" and $id) {
		$domain = GetDomainById($id);
		$zone = GetZoneByDomainOrderId($domain->id);

		if ($domain->uid == $_SESSION["userId"] and ($zone->privacy or $domain->privacy)) {
			$profile = GetUserProfileByUserId($_SESSION['userId'],$domain->profileId);

			if ($zone->privacy == "person" and $profile->org == "3") { 
				print $_lang[Error].": ".$_lang[DomainPrivacyProfileError]."<BR><BR>";
			} 
			else {
				print "<form action=billing.php method=post>";
				print "<input type=hidden name=do value=$do>";
				print "<input type=hidden name=sub value=updateprivacy>";
				print "<input type=hidden name=id value=$id>";
			        print "<table class='rpTable' cellpadding=3>";
	       			print "<tr><td align=center class=$font_head><B>".$_lang[DomainsPrivacyFor]." $domain->domain</b></td></tr>";

				if ($zone->privacy_cost and ($zone->privacy_payafterdisable or !$domain->privacy or $domain->todateprivacy == "0000-00-00" or $domain->leftdaysprivacy < 0)) { $privacyAddonCost = round($zone->privacy_cost*CURK,2)." ".CURS."/".$_lang[OrderYear]; }
				else { $privacyAddonCost = $_lang[OrderFree]; }
				$privacyAddonCost = " (".$privacyAddonCost.")";

				print "<tr><td align=center><input class=input type=radio name=privacy value=1> ".$_lang[DomainPrivacyEnable].$privacyAddonCost." <input class=input type=radio name=privacy value=0> ".$_lang[DomainPrivacyDisable]."</td></tr>";
		       		print "<tr><td align=center class=$font_head><input class=button type=submit value='".$_lang[Change]."'></td></tr>";
				print "</table>";
				print "</form>";
			}
		}
		else { print $_lang[ErrorBadId]."<BR><BR>"; }
	}

	if ($sub == "massns") {
		if ($sub2 == "change") {
			$ns1=trim(mb_strtolower($ns1)); $ns1 = preg_replace("/\.$/ui","",$ns1);
			$ns2=trim(mb_strtolower($ns2)); $ns2 = preg_replace("/\.$/ui","",$ns2);
			$ns3=trim(mb_strtolower($ns3)); $ns3 = preg_replace("/\.$/ui","",$ns3);
			$ns4=trim(mb_strtolower($ns4)); $ns4 = preg_replace("/\.$/ui","",$ns4);

			if (!$domains) { $error = $_lang[DomainsErrorMassNoDomains];}
			else if (!$ns1) {$error=$_lang[OrderErrorNoPrymaryNS];}
			else if (!preg_match("/^[a-z0-9]{1}[a-z0-9-]*\.[a-z0-9-.]*[a-z0-9]{1}$/u",$ns1)) {$error=$_lang[OrderErrorNoPrymaryNS];}
			else if (preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/u",$ns1)) {$error=$_lang[OrderErrorNoPrymaryNS];}
			else if (!$ns2) {$error=$_lang[OrderErrorNoSecondaryNS];}
			else if (!preg_match("/^[a-z0-9]{1}[a-z0-9-]*\.[a-z0-9-.]*[a-z0-9]{1}$/u",$ns2)) {$error=$_lang[OrderErrorNoSecondaryNS];}
			else if (preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/u",$ns2)) {$error=$_lang[OrderErrorNoSecondaryNS];}
			else if ($ns3 and !preg_match("/^[a-z0-9]{1}[a-z0-9-]*\.[a-z0-9-.]*[a-z0-9]{1}$/u",$ns3)) {$error=$_lang[OrderErrorNoThirdNS];}
			else if ($ns3 and preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/u",$ns3)) {$error=$_lang[OrderErrorNoThirdNS];}
			else if ($ns4 and !preg_match("/^[a-z0-9]{1}[a-z0-9-]*\.[a-z0-9-.]*[a-z0-9]{1}$/u",$ns4)) {$error=$_lang[OrderErrorNoFourNS];}
			else if ($ns4 and preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/u",$ns4)) {$error=$_lang[OrderErrorNoFourNS];}
			else if ($ns1 == $ns2) {$error=$_lang[OrderErrorNoSecondaryNS];}
			else if ($ns3 and ($ns3 == $ns1 or $ns3 == $ns2)) {$error=$_lang[OrderErrorNoThirdNS];}
			else if ($ns4 and ($ns4 == $ns1 or $ns4 == $ns2 or $ns4 == $ns3)) {$error=$_lang[OrderErrorNoFourNS];}
			else {
			        print "<table class='rpTable' cellpadding=3>";
		       		print "<tr><td colspan=3 align=center class=$font_head><B>".$_lang[DomainsMassNS]."</b></td></tr>";

				$changeDNStimeout = GetSetting("changeDNStimeout");
				$manager_email = GetSetting("manager_email");

				$doms = @mb_split("\r\n",$domains);
				while (list($k,$dom) = @each($doms)) {
					if ($dom) {
						$domain = GetDomainByDomain($dom);
						$zone = GetZoneByDomainOrderId($domain->id);

						print "<tr><td>$dom</td><td width=10 align=center>&nbsp;:&nbsp;</td><td>";

						if ($domain->uid == $_SESSION["userId"]) {

							if ($domain->lastDNSchangeTime) {
								$minutes = (time() - $domain->lastDNSchangeTime) / 60;
							} else {
								$minutes = 0;
							}

							if ($minutes and $changeDNStimeout and $minutes <= $changeDNStimeout) {print $_lang[DomainsErrorDNSTimeout]." (".round($minutes,2)."/$changeDNStimeout)"; }
							else if ($zone->enableCheckNS and !checkDNS($domain->domain, $zone->id, $ns1, $ns2, $ns3, $ns4)) { print $GLOBALerror; }
							else if ($domain->todate == "0000-00-00" or $domain->status != "1" or $domain->leftdays < 0) { print $_lang[DomainsErrorInactive]; }
							else {
								if ($domain->autoregby) {
									if (updateNS($domain->id,$ns1,$ns2,$ns3,$ns4)) {
										@mysql_query("update orders_domains set lastDNSchangeTime=".time().",ns1='$ns1',ns2='$ns2',ns3='$ns3',ns4='$ns4',ns1ip='',ns2ip='',ns3ip='',ns4ip='' where id='$domain->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

										print $_lang[DomainsChangeDNSSuccess];
									} else {
										print $_lang[DomainsErrorChangeDNS]." ".$GLOBALerror."";
									}
								} else {
									$nsss = "NS1: $ns1\nNS2: $ns2";
									if ($ns3) {$nsss = $nsss."\nNS3: $ns3";}
									if ($ns4) {$nsss = $nsss."\nNS4: $ns4";}

									$subject_msg = "�?зменение DNS-серверов для $domain->domain";
									$message = "Пользователь ".$_SESSION["userLogin"]." заказал смену DNS-серверов для домена $domain->domain (ID # $domain->id) на:\n\n$nsss\n\n--\nRootPanel";

									$admEmails=GetAdminEmailsWhereTrueParam("senddns");
									if (count($admEmails) > 0) {
										WriteMailLog($subject_msg,$message);
									}
									while (list($i,$em) = @each($admEmails)) {
										sendmail($em,'',$manager_email,$subject_msg,$message);
									}

									if (GetSetting("smsGateway")) {
										$smsmsg = "Nuzhno smenit DNS for $domain->domain, user ".$_SESSION["userLogin"];

										$admIds=GetAdminIdsWhereTrueParam("sms_senddns");
										while (list($i,$aid) = @each($admIds)) {
											sendSMS('',$aid,$smsmsg);
										}
									}
	
									@mysql_query("update orders_domains set lastDNSchangeTime=".time()." where id='$domain->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

									print $_lang[DomainsChangeDNSSendSuccess];
								}
							}
						} else {
							print $_lang[DomainsErrorDomainOrdersNotFound];
						}

						print "</td></tr>";
					}
				}
				print "</table><BR>";
			}

			if ($error) { print $_lang[Error].": ".$error."<BR><BR>";}

		}

		print "<form action=billing.php method=post>";
		print "<input type=hidden name=do value=$do>";
		print "<input type=hidden name=sub value=massns>";
		print "<input type=hidden name=sub2 value=change>";
	        print "<table class='rpTable' cellpadding=3>";
       		print "<tr><td colspan=2 align=center class=$font_head><B>".$_lang[DomainsMassNS]."</b></td></tr>";

		print "<tr><td colspan=2><code class=warn>*</code> ".$_lang[OrderDomains].": <img src=./_rootimages/question.gif alt='".$_lang[OrderDomainsComment]."'></td></tr>";
		print "<tr><td colspan=2>";
		print "<textarea class=input name=domains cols=46 rows=6>$domains</textarea></td></tr>";


		print "<tr><Td><code class=warn>*</code> ".$_lang[DomainsNS1].":</td><td><input class=input type=text size=30 name=ns1 value=\"$ns1\" ></td></tr>";
		print "<tr><Td><code class=warn>*</code> ".$_lang[DomainsNS2].":</td><td><input class=input type=text size=30 name=ns2 value=\"$ns2\" ></td></tr>";
		print "<tr><Td>".$_lang[DomainsNS3].":</td><td><input class=input type=text size=30 name=ns3 value=\"$ns3\" ></td></tr>";
		print "<tr><Td>".$_lang[DomainsNS4].":</td><td><input class=input type=text size=30 name=ns4 value=\"$ns4\" ></td></tr>";
       		print "<tr><td colspan=2 align=center class=$font_head><input class=button type=submit value='".$_lang[Change]."'></td></tr>";
		print "</table>";
		print "</form>";

		foot("utf-8");
		mclose();
		exit;
	}

	if ($sub == "dns" and $id) {
		$domain = GetDomainById($id);

		if ($domain->uid == $_SESSION["userId"]) {
			if ($sub2 == "update" and $domain->dnsServerId) {
				$updateResult = updateDNSRecordsAtServer($id,$dns,0);

				if (!$updateResult) { print $_lang[Error].": ".$GLOBALerror."<BR><BR>"; }
				else { print $_lang[DomainsDNSRecordsUpdateSuccess]."<BR><BR>"; }
			}

			if ($sub2 == "create" and $domain->dnsServerId) {
				$updateResult = createDNSRecordsAtServer($id,$rname,$rtype,$raddr,$rprio,0);

				if (!$updateResult) { print $_lang[Error].": ".$GLOBALerror."<BR><BR>"; }
				else {print $_lang[DomainsDNSRecordsCreateSuccess]."<BR><BR>"; $rname="";$rtype="";$raddr="";$rprio="";}
			}

			if ($sub2 == "delete" and $domain->dnsServerId and $rkey) {
				$rkey2=urlencode(htmlDecode($rkey2));
				$updateResult = deleteDNSRecordsAtServer($id,$rkey,$rkey2,0);

				if (!$updateResult) { print $_lang[Error].": ".$GLOBALerror."<BR><BR>"; }
				else { print $_lang[DomainsDNSRecordsDeleteSuccess]."<BR><BR>"; }
			}

			if ($domain->dnsServerId) {
				$dns = getDNSRecordsAtServer($id,0);
				$server = GetServers($domain->dnsServerId);

				if ($server->type != "isp") { $readOnly = "readonly"; $disabled="disabled"; } else { $readOnly = ""; $disabled = ""; }

				if (!$dns) { print $_lang[DomainsErrorCantGetDNSRecords]." ".$GLOBALerror."<BR><BR>"; }
			}

			print "<form action=billing.php method=post>";
			print "<input type=hidden name=do value=$do>";
			print "<input type=hidden name=sub value=$sub>";
			print "<input type=hidden name=sub2 value=update>";
			print "<input type=hidden name=id value=$id>";
		        print "<table class='rpTable' cellpadding=3>";
	       		print "<tr><td colspan=5 align=center class=$font_head><B>".$_lang[DomainsDNSRecordsFor]." $domain->domain</b></td></tr>";

			$i=0;
			while(list($k,$v) = @each($dns)) {
				$delete="<A class=rootlink href='?do=$do&sub=$sub&sub2=delete&id=$id&rkey=".$dns[$i][key]."&rkey2=".$dns[$i][key2]."' onclick=\"javascript: return confirm('".$_lang[DomainRecordDeleteAlert]."');\"><img src=./_rootimages/del.gif style='padding-right: 5px' border=0 alt='".$_lang[DomainsDeleteDomainRecord]."'></a>";
				if (!$dns[$i][name]) { $dns[$i][name] = "$domain->domain."; }

#				if ($dns[$i][type] == "NS") { $curReadonly = "readonly"; $curDisabled = "disabled";} else { $curReadonly = ""; $curDisabled = ""; }

				if ($dns[$i][type] == "NS") {
					print "<input type=hidden name=\"dns[$i][key]\" value=\"".$dns[$i][key]."\"><input type=hidden name=\"dns[$i][name]\" value=\"".$dns[$i][name]."\">";
					print "<input type=hidden name=\"dns[$i][type]\" value=\"".$dns[$i][type]."\">";
					print "<input type=hidden name=\"dns[$i][addr]\" value='".$dns[$i][addr]."'>";
				} else {
					print "<tr>";

					print "<td><input type=hidden name=\"dns[$i][key]\" value=\"".$dns[$i][key]."\"><input type=text $readOnly $curReadonly class=input size=30 name=\"dns[$i][name]\" value=\"".$dns[$i][name]."\"></td>";

					print "<td><select $disabled $curDisabled class=input id=select$i name=\"dns[$i][type]\" onchange=\" if (document.getElementById('select$i').options[document.getElementById('select$i').selectedIndex].value == 'MX') {document.getElementById('prio$i').disabled=0;} else {document.getElementById('prio$i').disabled=1;}\">";
					?><option value=A <? if ($dns[$i][type] == "A") { print "selected"; } ?>>A</option><?
					?><option value=CNAME <? if ($dns[$i][type] == "CNAME") { print "selected"; } ?>>CNAME</option><?
					?><option value=NS <? if ($dns[$i][type] == "NS") { print "selected"; } ?>>NS</option><?
					?><option value=MX <? if ($dns[$i][type] == "MX") { print "selected"; } ?>>MX</option><?
					?><option value=TXT <? if ($dns[$i][type] == "TXT") { print "selected"; } ?>>TXT</option><?
					print "</select></td>";

					?><td><input $readOnly $curReadonly type=text id=prio<? print $i?> <? if ($dns[$i][type] != "MX") { print "disabled"; } ?> class=input size=1 name="dns[<? print $i?>][prio]" value="<? print $dns[$i][prio]?>"></td><?

					print "<td><input type=text $readOnly $curReadonly class=input size=40 name=\"dns[$i][addr]\" value='".$dns[$i][addr]."'></td>";

#					if ($dns[$i][type] != "NS") { print "<td>$delete</td>"; } else { print "<td></td>"; }
					print "<td>$delete</td>";

					print "</tr>";
				}

				$i++;
			}

	       		print "<tr height=20><td colspan=5 align=center class=$font_head>"; if ($server->type == "isp") { print "<input class=button type=submit value='".$_lang[Change]."'>"; } print "</td></tr>";
			print "</form>";

			print "<form action=billing.php method=post>";
			print "<input type=hidden name=do value=$do>";
			print "<input type=hidden name=sub value=$sub>";
			print "<input type=hidden name=sub2 value=create>";
			print "<input type=hidden name=id value=$id>";

			print "<tr>";
			print "<td><input type=text class=input size=30 name=\"rname\" value=\"".$rname."\"></td>";
			print "<td><select class=input name=\"rtype\" id=rtype onchange=\" if (document.getElementById('rtype').options[document.getElementById('rtype').selectedIndex].value == 'MX') {document.getElementById('rprio').disabled=0;} else {document.getElementById('rprio').disabled=1;}\">";
			?><option value=A <? if ($rtype == "A") { print "selected"; } ?>>A</option><?
			?><option value=CNAME <? if ($rtype == "CNAME") { print "selected"; } ?>>CNAME</option><?
			?><option value=NS <? if ($rtype == "NS") { print "selected"; } ?>>NS</option><?
			?><option value=MX <? if ($rtype == "MX") { print "selected"; } ?>>MX</option><?
			?><option value=TXT <? if ($rtype == "TXT") { print "selected"; } ?>>TXT</option><?
			print "</select></td>";
			?><td><input type=text class=input size=1 name="rprio" id=rprio value="<? print $rprio?>" <? if ($rtype != "MX") { print "disabled"; } ?>></td><?
			print "<td><input type=text class=input size=40 name=\"raddr\" value=\"".$raddr."\"></td>";
			print "</tr>";
	       		print "<tr><td colspan=5 align=center class=$font_head><input class=button type=submit value='".$_lang[Add]."'></td></tr>";

			print "</table>";
			print "</form>";
		}
		else { print $_lang[ErrorBadId]."<BR><BR>"; }

		foot("utf-8");
		mclose();
		exit;
	}


        if ($sub == 'delete' and $id) {
                @mysql_query("delete from orders_domains where id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

		$bs = @mysql_query("select * from bills where domain_id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		while ($b = @mysql_fetch_object($bs)) {
			if (!$b->status) {
				@mysql_query("delete from bills where id='$b->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			} else {
				@mysql_query("update bills set archived=1 where id='$b->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			}
		}

                print $_lang[DomainsDeleteSuccess]."<BR><BR>";
        }

	if ($sub == "updatecontact" and $id) {
		$domain = GetDomainById($id);

		if ($domain->uid == $_SESSION["userId"] and $profileId) {
			$zone = GetZoneByDomainOrderId($domain->id);
			$registrator = GetRegistratorTypeById($domain->autoregby);

			$profile = GetUserProfileByUserId($_SESSION["userId"], $profileId);

			$localContactUser = GetUserByLogin(GetSetting("localContactUser")); 
			if ($localContactUser->id and $zone->localContact and $domain->localContact) {
				$currentProfile = GetUserProfileByUserId($localContactUser->id, $zone->localContact);
			} else {
				$currentProfile = GetUserProfileByUserId($_SESSION["userId"], $domain->profileId);
			}

			if (($registrator == "webnames" or $registrator == "regru") and $profile->id != $currentProfile->id and $profile->org != $currentProfile->org) { print $_lang[DomainsChangeProfileTypeError]."<BR><BR>"; $sub = "updcontact"; }
			else {
				if (updateDomainContact($domain->id, $profileId, 0)) {
					@mysql_query("UPDATE orders_domains SET localContact='0' WHERE id='$domain->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
					print $_lang[DomainsChangeContactSuccess]."<BR><BR>";
				} else {
					print $_lang[DomainsChangeContactError]." ".$GLOBALerror."<BR><BR>";
					$sub = "updcontact";
				}
			}

		}
		else { print $_lang[ErrorBadId]."<BR><BR>"; }
	}

	if ($sub == "updcontact" and $id) {
		$domain = GetDomainById($id);

		if ($domain->uid == $_SESSION["userId"]) {
			$zone = GetZoneByDomainOrderId($domain->id);
			$registrator = GetRegistratorTypeById($domain->autoregby);

			if ($domain->todate != "0000-00-00" and $domain->status == "1" and $domain->leftdays >= 0 and ($registrator == "r01" or $registrator == "internetx" or $registrator == "ukrnames" or $registrator == "ppua" or $registrator == "websitews" or $registrator == "dotfm" or $registrator == "niclv" or $registrator == "epag" or $registrator == "todaynic" or $registrator == "rrpproxy" or $registrator == "webnames" or $registrator == "regru" or $registrator == "cnic" or $registrator == "gfx" or $registrator == "internetbs" or $registrator == "hostmasterepp" or $registrator == "nicdpua" or $registrator == "subreg" or $registrator == "networking4all" or $registrator == "pskz" or $registrator == "rootpanel")) {
				if ($domain->localContact and ($registrator == "r01" or $registrator == "internetx" or $registrator == "epag" or $registrator == "rrpproxy" or $registrator == "webnames" or $registrator == "regru" or $registrator == "rootpanel")) {
					print $_lang[ErrorUpdContactWithLocalContact]."<BR><BR>"; 
				} else {
					if ($registrator == "ukrnames") { print "".$_lang[DomainsWarningUkrnames]."<BR><BR>"; }
					if (($registrator == "webnames" or $registrator == "regru") and preg_match("/\.{0,1}[ru|su|рф]$/ui",$zone->zone)) { print "".$_lang[DomainsWarningUpdateRUSU]."<BR><BR>"; }

					print "<form action=billing.php method=post>";
					print "<input type=hidden name=do value=$do>";
					print "<input type=hidden name=sub value=updatecontact>";
					print "<input type=hidden name=id value=$id>";
			        	print "<table class='rpTable' cellpadding=3>";
			       		print "<tr><td colspan=2 align=center class=$font_head><B>".$_lang[DomainsChangeContactFor]." $domain->domain</b></td></tr>";
					print "<tr><Td colspan=2>".$_lang[DomainsChangeContactSelectProfile].":</td></tr>";
					print "<tr><Td colspan=2>"; printProfileSelect($_SESSION["userId"],$profileId); print "</td></tr>";
		       			print "<tr><td colspan=2 align=center class=$font_head><input class=button type=submit value='".$_lang[Change]."'></td></tr>";
					print "</table>";
					print "</form>";
				}
			}
			else { print $_lang[ErrorBadId]."<BR><BR>"; }
		}
		else { print $_lang[ErrorBadId]."<BR><BR>"; }
		
	}

	if ($sub == "push2" and $id) {
		$domain = GetDomainById($id);

		if ($domain->uid == $_SESSION["userId"]) {
			$registrator = GetRegistratorTypeById($domain->autoregby);
			$user = GetUserById($_SESSION["userId"]);

			if ($domain->todate != "0000-00-00" and $domain->status == "1" and $domain->leftdays >= 0 and ($registrator != "nicru" and $registrator != "epag" and $registrator != "rrpproxy" and $registrator != "rootpanel" and $registrator != "internetx" and $registrator != "directi" and $registrator != "mail")) {
				if ((GetSetting("allowDomainPush") and $user->allowDomainPush != "2") or (!GetSetting("allowDomainPush") and $user->allowDomainPush == "1")) {
					$newUser = GetUserByLogin($clientLogin);
					if ($newUser->id) {
					        @mysql_query("UPDATE orders_domains SET uid='$newUser->id',profileId='$newUser->defaultProfileId',host_id='0' WHERE uid='".$_SESSION["userId"]."' and id='$id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
						if ($domain->host_id) {
						        @mysql_query("UPDATE orders SET domain_reg='0' WHERE id='$domain->host_id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
						}

					     	addUserLog($_SESSION['userId'],'pushDomainOut',"$domain->domain => $newUser->login");
					     	addUserLog($newUser->id,'pushDomainIn',"$domain->domain => ".$_SESSION['userLogin']);

						$tpl=GetTpl('email_touser_domain_push', $user->lang);
						$subject=$tpl[subject]; $template=$tpl[template];
						if ($subject and $template) {
							$company_name=GetSetting('company_name');
							$company_url=GetSetting('company_url');
							$support_url=GetSetting('support_url');
							$manager_email=GetSetting('manager_email');

							$template = str_replace('{company_name}',$company_name,$template);
						     	$template = str_replace('{company_url}',$company_url,$template);
			     				$template = str_replace('{support_url}',$support_url,$template);
							$template = str_replace('{domain}',$domain->domain,$template);
							$template = str_replace('{newuser}',$newUser->login,$template);

							WriteMailLog($subject,$template,$user->id);
							sendmail($user->email,$company_name,$manager_email,$subject,$template,'','',$tpl[type]);
							sendmail($user->email2,$company_name,$manager_email,$subject,$template,'','',$tpl[type]);
						}


						print $_lang[DomainsPushSuccess]."<BR><BR>";
					}
					else { print $_lang[FundErrorNoUser]."<BR><BR>"; $sub = "push"; }
				} 
				else { print $_lang[ErrorPushCant]."<BR><BR>"; $sub = "push"; }
			} 
			else { print $_lang[ErrorPushCant]."<BR><BR>"; $sub = "push"; }
		}
		else { print $_lang[ErrorBadId]."<BR><BR>"; $sub = "push"; }
	}

	if ($sub == "push" and $id) {
		$domain = GetDomainById($id);

		if ($domain->uid == $_SESSION["userId"]) {
			$registrator = GetRegistratorTypeById($domain->autoregby);
			$user = GetUserById($_SESSION["userId"]);

			if ($domain->todate != "0000-00-00" and $domain->status == "1" and $domain->leftdays >= 0 and ($registrator != "nicru" and $registrator != "epag" and $registrator != "rrpproxy" and $registrator != "rootpanel" and $registrator != "internetx" and $registrator != "directi" and $registrator != "mail")) {
				if ((GetSetting("allowDomainPush") and $user->allowDomainPush != "2") or (!GetSetting("allowDomainPush") and $user->allowDomainPush == "1")) {
					print "<form action=billing.php method=post>";
					print "<input type=hidden name=do value=$do>";
					print "<input type=hidden name=sub value=push2>";
					print "<input type=hidden name=id value=$id>";
				        print "<table class='rpTable' cellpadding=3>";
			       		print "<tr><td colspan=2 align=center class=$font_head><B>".$_lang[DomainsPushDomain]." $domain->domain</b></td></tr>";
					print "<tr><Td>".$_lang[DomainsPushUserLogin].":</td><Td><input type=text class=input size=15 name=clientLogin> </td></tr>";
		       			print "<tr><td colspan=2 align=center class=$font_head><input class=button type=submit value='".$_lang[Push]."'></td></tr>";
					print "</table>";
					print "</form>";
				} else { print $_lang[ErrorPushCant]."<BR><BR>"; }
			} else { print $_lang[ErrorPushCant]."<BR><BR>"; }
		}
		else { print $_lang[ErrorBadId]."<BR><BR>"; }
	}

	if ($searchDomain) { $where = "and domain LIKE '%$searchDomain%'"; } else { $where = ""; }

        getfont();
	$user = GetUserById($_SESSION["userId"]);

        $r=@mysql_query("select * from orders_domains WHERE uid='".$_SESSION["userId"]."' $where order by id desc") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
        $rows = mysql_num_rows($r);
        list($start, $perPage, $txt) = MakePages($page, $rows);

        ?>
        <table class='rpTable' cellpadding=3>
        <tr>
		<td colspan=4 align=left valign=top><B><? print $_lang[DomainsUtils]?>:</B> [ <A href=?do=<? print $do?>&sub=massns class=rootlink><? print $_lang[DomainsMassNS]?></a> ]</td>
		<td colspan=3 align=right valign=top><form method=post><input type=hidden name=do value=<? print $do?>><B><? print $_lang[DomainsDomain]?>:</B> <input type=text class=input size=15 name=searchDomain> <input type=submit class=button value="<? print $_lang[DomainsDomainSearch]?>"></form></td>
	</tr>
        <tr><td colspan=7 align=right><? print $txt?></td></tr>
        <tr class=<? print $font_head?> align=center><td>ID</td><Td><? print $_lang[DomainsDate]?></td><td><? print $_lang[DomainsDomain]?></td><td><? print $_lang[DomainsEnd]?></td><td><? print $_lang[DomainsLeftDays]?></td><td><? print $_lang[BillsStatus]?></td><td></td></tr>
        <?
        $r=@mysql_query("select *,TO_DAYS(todate)-TO_DAYS(NOW()) as leftdays from orders_domains where uid='".$_SESSION["userId"]."' $where order by id desc LIMIT $start,$perPage") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
        $cnt=0;
        getfont();
        while ($rr = @mysql_fetch_object($r)) {
                getfont();

                $b=mysql_query("select * from bills where archived=0 and domain_id = '$rr->id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
                $bills=mysql_num_rows($b);
                $b=mysql_fetch_object($b);

                $bp=mysql_query("select * from bills where archived=0 and domain_id = '$rr->id' and uid='".$_SESSION["userId"]."' and !(status='0')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
                $billspayed=mysql_num_rows($bp);
                $billsNonPayed = $bills-$billspayed;

		$zone = GetZoneById($rr->zone_id);
		$registrator = GetRegistratorTypeById($rr->autoregby);

		if ($rr->leftdays == "") {$leftdays = "-";}
		else if ($rr->leftdays <= 30 and $rr->startdate != "0000-00-00") {$leftdays = "<font color=red>".$rr->leftdays."</font>"; }
		else {$leftdays = $rr->leftdays; }

                if ($rr->startdate != "0000-00-00") { $todate = mydate($rr->todate); } else { $todate = "-"; }

		if ($rr->todate != "0000-00-00" and $rr->status == "1" and $rr->leftdays >= 0 and ($registrator == "r01" or $registrator == "internetx" or $registrator == "ukrnames" or $registrator == "ppua" or $registrator == "websitews" or $registrator == "dotfm" or $registrator == "niclv" or $registrator == "epag" or $registrator == "todaynic" or $registrator == "rrpproxy" or $registrator == "webnames" or $registrator == "regru" or $registrator == "cnic" or $registrator == "gfx" or $registrator == "internetbs" or $registrator == "hostmasterepp" or $registrator == "nicdpua" or $registrator == "subreg" or $registrator == "networking4all" or $registrator == "pskz" or $registrator == "rootpanel")) {
			$updcontact="<A class=rootlink href=?do=$do&sub=updcontact&id=$rr->id><img src=./_rootimages/dns.gif style='padding-right: 5px' border=0 alt='".$_lang[DomainsChangeContact]."'>$_lang[DomainsChangeContact]</a><BR>";
		} else {
			$updcontact="";
		}

		if ($rr->todate != "0000-00-00" and $rr->status == "1" and $rr->leftdays >= 0) {
			$dns="<A class=rootlink href=?do=$do&sub=ns&id=$rr->id><img src=./_rootimages/dns.gif style='padding-right: 5px' border=0 alt='".$_lang[DomainsChangeDNS]."'>$_lang[DomainsChangeDNS]</a><BR>";
		} else {
			$dns="";
		}

		if ($rr->todate != "0000-00-00" and $rr->status == "1" and $rr->leftdays >= 0 and ($zone->privacy or $rr->privacy)) {
			$privacy="<A class=rootlink href=?do=$do&sub=privacy&id=$rr->id><img src=./_rootimages/dns.gif style='padding-right: 5px' border=0 alt='".$_lang[DomainsChangePrivacy]."'>$_lang[DomainsChangePrivacy]</a><BR>";
		} else {
			$privacy="";
		}

		if ($rr->transfer) {
			$newreg = $_newregmin[3];
		} else {
			$newreg = $_newregmin[1];
		}

		if ($rr->todate != "0000-00-00" and $rr->status == "1" and $rr->dnsServerId) {
			$dnsext="<A class=rootlink href=?do=$do&sub=dns&id=$rr->id><img src=./_rootimages/dns.gif style='padding-right: 5px' border=0 alt='".$_lang[DomainsControlDNS]."'>$_lang[DomainsControlDNS]</a><BR>";
		} else {
			$dnsext="";
		}

                if ($billspayed > 0 or $rr->todate != "0000-00-00") {
                        $delete='';
		} else {
                        $delete="<A class=rootlink href=?do=$do&sub=delete&id=$rr->id onclick=\"javascript: return confirm('".$_lang[DomainsDeleteAlert]."');\"><img src=./_rootimages/del.gif style='padding-right: 5px' border=0 alt='".$_lang[DomainsDeleteDomain]."'>$_lang[DomainsDeleteDomain]</a><BR>";

		}
                if ($billsNonPayed == 0 and $rr->todate != "0000-00-00") {
			if ($zone->daysRenew and $zone->daysRenew < $rr->leftdays) {
	                        $renew='';
			} else {
	                        $renew="<a class=rootlink href=?do=renewdomain&domain_id=$rr->id><img src=./_rootimages/renew.gif style='padding-right: 5px' border=0 alt='".$_lang[DomainsRenewDomain]."'>$_lang[DomainsRenewDomain]</a>";
			}
                } else {
                        $renew='';
                }

		if (GetSetting("dogovor_enable") and !$rr->host_id) {
			$dogovor="<a class=rootlink href='' onClick=\"popupWin = window.open('billing.php?do=orders&domain_id=$rr->id&sub=dogovor', 'dogovor', 'location,width=650,height=600,top=0,scrollbars=yes'); popupWin2 = window.open('billing.php?do=orders&domain_id=$rr->id&sub=dodatok', 'dodatok', 'location,width=650,height=600,top=20,left=20,scrollbars=yes'); popupWin.focus(); return false;\"><img src=./_rootimages/dogovor.gif style='padding-right: 5px' border=0 alt='".$_lang[OrdersPrintDogovor]."'>$_lang[OrdersPrintDogovor]</a><BR>";
		} else {
			$dogovor="";
		}

		if ($rr->remarkUser or $rr->comment) {
			$rr->remarkUser = preg_replace("/\n/ui","<BR>",$rr->remarkUser."<BR>".$rr->comment);
			$remark="<span title='<B>".$_lang[OrderRemark].":</b><BR>$rr->remarkUser'><img src=./_rootimages/question2.gif style='padding-right: 5px' border=0>$_lang[OrderRemark]</span><BR>";
		} else {
			$remark="";
		}

		if ($rr->todate != "0000-00-00" and $rr->status == "1" and $rr->leftdays >= 0 and ($registrator != "nicru" and $registrator != "epag" and $registrator != "rrpproxy" and $registrator != "rootpanel" and $registrator != "internetx" and $registrator != "directi" and $registrator != "mail")) {
			if ((GetSetting("allowDomainPush") and $user->allowDomainPush != "2") or (!GetSetting("allowDomainPush") and $user->allowDomainPush == "1")) {
				$push = "<A class=rootlink href=?do=$do&sub=push&id=$rr->id><img src=./_rootimages/push.gif style='padding-right: 5px' border=0 alt='".$_lang[DomainsPush]."'>$_lang[DomainsPush]</a><BR>";
			} else {
				$push = "";
			}
		} else {
			$push = "";
		}

                $statusDomain = "<img src=./_rootimages/obrabotan_".$rr->status."_small.gif border=0 alt='".$_status[$rr->status]."' title='".$_status[$rr->status]."'>";

                print "
                <tr class=$font_row height=30>
		 <td align=center>$rr->id</td>
		 <td align=center>".mydate($rr->orderdate)."</td>
                 <td>$registrator->type<B>$rr->domain</b><BR>[$newreg]</td>
                 <td align=center>$todate</td>
                 <td align=center>$leftdays</td>
		 <td align=center>$statusDomain</td>
                 <td align=left valign=middle>$remark$dogovor$updcontact$dns$privacy$dnsext$push<A class=rootlink href=?do=bills&param=domain_id&search=$rr->id><img src=./_rootimages/bills.gif style='padding-right: 5px' border=0 alt='".$_lang[BillsTitle].": $bills'>$_lang[BillsTitle]: $bills</a><BR>$renew$delete</td>
                </tr>
                ";

                $cnt++;
        }
        ?>
        <tr class=<? print $font_head?>><Td colspan=7><? print $_lang[DomainsDomainsTotal]?>: <? print $rows?>, <? print $_lang[DomainsOnPage]?>: <? print $cnt?></td></tr>
        <tr><td colspan=7 align=right><? print $txt?></td></tr>

        </table>
        <?
        foot('utf-8');
}

if ($do == "shop") {
	head('utf-8',$_lang[ShopTitle]);
	print "<H1 class=pagetitle>".$_lang[ShopTitle]."</H1><hr class=hr>";

	$r=@mysql_query("select * from bills where archived=0 and status = '0' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
	if (mysql_num_rows($r) > 0) {
		print "<font color=red>".$_lang[BillsNeOplachenoSchetov].": ".mysql_num_rows($r).". ".$_lang[BillsGoto]." <A class=rootlink href=?do=bills>".$_lang[BillsTitle]."</a> ".$_lang[BillGotoFor].".</font><BR><BR>";
	}

	if ($sub == "licenseUpdate" and $type and $id) {
		$order = GetOrderShopById($id);
		$item = GetShopItemById($order->item);

		if ($order->uid == $_SESSION["userId"] and $order->status and $order->field3 and ($item->type == "ispmanagerlite" or $item->type == "ispmanagerlitefull" or $item->type == "ispmanagerlitetrial" or $item->type == "ispmanagerpro" or $item->type == "ispmanagerprofull" or $item->type == "ispmanagerprotrial" or $item->type == "vdsmanagerlinux" or $item->type == "vdsmanagerlinuxfull" or $item->type == "vdsmanagerfreebsd" or $item->type == "vdsmanagerfreebsdfull" or $item->type == "billmanagerstandart" or $item->type == "billmanageradvanced" or $item->type == "billmanagercorporate" or $item->type == "dsmanager" or $item->type == "dsmanagerfull" or $item->type == "dnsmanagerfull" or $item->type == "ipmanagerfull")) {
			if ($sub2 == "update" and $oldLValue) {
				if ($newLValue != $oldLValue) {
					if ($type == "ip" and !preg_match("/^\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}$/",$newLValue)) { print "<font color=red>".$_lang[OrderErrorNoLicenseIP]."</font><BR><BR>";}
					if (updateShopOrder($order->id,$type,$newLValue)) {
						if ($type == "name") {
							@mysql_query("update orders_shop set field1='$newLValue' where id='$id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
						} else if ($type == "ip") {
							@mysql_query("update orders_shop set field2='$newLValue' where id='$id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
						}

						print $_lang[ShopChangeLicenseSuccess]."<BR><BR>";

						$manager_email=GetSetting('manager_email');

						$subject = "�?зменение лицензии";
						$template = "Пользователь ".$_SESSION["userLogin"]." изменил имя или ip лицензии для товара $item->name (ID # $order->id) на:\n\nfield: $type\nvalue: $newLValue\n\n--\nRootPanel";

						$admEmails=GetAdminEmailsWhereTrueParam("sendneworder");
						if (count($admEmails) > 0) {
							WriteMailLog($subject,$template);
						}
						while (list($i,$em) = @each($admEmails)) {
							sendmail($em,'',$manager_email,$subject,$template);
						}

					} else {
						print "<font color=red>".$_lang[ShopChangeLicenseError]." ($GLOBALerror)</font><BR><BR>";
					}
				}

				$order = GetOrderShopById($id);
			}

			if ($type == "name") {
				$fieldName = $_lang[OrderLisenseName];
				$fieldValue = $order->field1;
			} else if ($type == "ip") {
				$fieldName = $_lang[OrderLisenseIP];
				$fieldValue = $order->field2;
				print $_lang[ShopChangeLicenseIPAlert]."<BR><BR>";
			}

			print "<form action=billing.php method=post>";
			print "<input type=hidden name=do value=$do>";
			print "<input type=hidden name=sub value=licenseUpdate>";
			print "<input type=hidden name=sub2 value=update>";
			print "<input type=hidden name=type value=$type>";
			print "<input type=hidden name=oldLValue value='$fieldValue'>";
			print "<input type=hidden name=id value=$id>";

		        print "<table class='rpTable' cellpadding=3>";
	       		print "<tr><td colspan=2 align=center class=$font_head><B>".$_lang[ShopChangeLicense]."</b></td></tr>";

			print "<tr><Td>".$fieldName.":</td><td><input class=input type=text size=30 name=newLValue value='$fieldValue'></td></tr>";
	       		print "<tr><td colspan=2 align=center class=$font_head><input class=button type=submit value='".$_lang[Change]."'></td></tr>";
			print "</table>";
			print "</form>";
		}
		else { print $_lang[ErrorBadId]."<BR><BR>"; }
	}

	if ($sub == 'delete' and $id) {
		$order = GetOrderShopById($id);
		if ($order->startdate == "0000-00-00" and $order->uid == $_SESSION["userId"]) {
			@mysql_query("delete from orders_shop where id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

			$bs = @mysql_query("select * from bills where shop_id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			while ($b = @mysql_fetch_object($bs)) {
				if (!$b->status) {
					@mysql_query("delete from bills where id='$b->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				} else {
					@mysql_query("update bills set archived=1 where id='$b->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				}
			}

			print $_lang[OrdersDeleteSuccess]."<BR><BR>";
		} else {
			print "<font color=red>".$_lang[OrdersErrorCantDelete]."</font><BR><BR>";
		}
	}

	getfont();

        $r=@mysql_query("select * from orders_shop WHERE uid='".$_SESSION["userId"]."' order by id desc") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
        $rows = mysql_num_rows($r);
        list($start, $perPage, $txt) = MakePages($page, $rows);

	?>
	<table class='rpTable' cellpadding=3>
        <tr><td colspan=7 align=right><? print $txt?></td></tr>
	<tr class=<? print $font_head?> align=center><td>ID</td><Td><? print $_lang[OrdersDate]?></td><td><? print $_lang[OrderItem]?></td><td><? print $_lang[OrdersEnd]?></td><td><? print $_lang[OrdersLeftDays]?></td><td><? print $_lang[BillsStatus]?></td><td></td></tr>
	<?
	$r=@mysql_query("select *,TO_DAYS(todate)-TO_DAYS(NOW()) as leftdays,TO_DAYS(NOW())-TO_DAYS(startdate) as daysFromBuy from orders_shop where uid='".$_SESSION["userId"]."' order by id desc LIMIT $start,$perPage") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
	$cnt=0;
	getfont();
	while ($rr = @mysql_fetch_object($r)) {
		getfont();
		$t=mysql_query("select * from shop_items where id = '$rr->item'");
		$t=mysql_fetch_object($t);
		$b=mysql_query("select * from bills where archived=0 and shop_id = '$rr->id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		$bills=mysql_num_rows($b);
		$b=mysql_fetch_object($b);
		$bp=mysql_query("select * from bills where archived=0 and shop_id = '$rr->id' and uid='".$_SESSION["userId"]."' and !(status='0')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		$billspayed=mysql_num_rows($bp);
		$billsNonPayed = $bills-$billspayed;

	        if ($t->costtype != "one" and $rr->leftdays != "") {
			if ($rr->leftdays <= 10 and $rr->startdate != "0000-00-00") {
				$leftDays = "<font color=red>".$rr->leftdays."</font>"; 
			} else {
				$leftDays = $rr->leftdays; 
			}
			$leftDays="<img src=./_rootimages/shop.gif border=0 alt='".$_lang[OrderItem]."'> ".$leftDays; 
		}
		else { $leftDays = "-"; }

        	if ($t->costtype != "one" and $rr->startdate != "0000-00-00") { $todate="<img src=./_rootimages/shop.gif border=0 alt='".$_lang[OrderItem]."'> ".mydate($rr->todate); }
	        else { $todate = "-"; }
		
		if ($billspayed > 0 or $rr->startdate != "0000-00-00") {
			$delete='';
		} else {
			$delete="<A class=rootlink href=?do=$do&sub=delete&id=$rr->id onclick=\"javascript: return confirm('".$_lang[OrdersDeleteAlert]."');\"><img src=./_rootimages/del.gif style='padding-right: 5px' border=0 alt='".$_lang[OrdersDeleteOrder]."'>$_lang[OrdersDeleteOrder]</a><BR>";
		}
		if ($t->costtype != "one" and $billsNonPayed == 0 and $rr->todate != "0000-00-00") {
			$renew="<a class=rootlink href=?do=renewshop&shop_id=$rr->id><img src=./_rootimages/renew.gif style='padding-right: 5px' border=0 alt='".$_lang[OrdersRenewOrder]."'>$_lang[OrdersRenewOrder]</a><BR>";
		} else {
			$renew='';
		}

		if ($rr->remarkUser or $rr->comment) {
			$rr->remarkUser = preg_replace("/\n/ui","<BR>",$rr->remarkUser."<BR>".$rr->comment);
			$remark="<span title='<B>".$_lang[OrderRemark].":</b><BR>$rr->remarkUser'><img src=./_rootimages/question2.gif style='padding-right: 5px' border=0>$_lang[OrderRemark]</span><BR>";
		} else {
			$remark="";
		}

		if (GetSetting("dogovor_shop_enable")) {
			$dogovor="<a class=rootlink href='' onClick=\"popupWin = window.open('billing.php?do=orders&shop_id=$rr->id&sub=dogovor', 'dogovor', 'location,width=650,height=600,top=0,scrollbars=yes'); popupWin2 = window.open('billing.php?do=orders&shop_id=$rr->id&sub=dodatok', 'dodatok', 'location,width=650,height=600,top=20,left=20,scrollbars=yes'); popupWin.focus(); return false;\"><img src=./_rootimages/dogovor.gif style='padding-right: 5px' border=0 alt='".$_lang[OrdersPrintDogovor]."'>$_lang[OrdersPrintDogovor]</a><BR>";
		} else {
			$dogovor="";
		}

		$downloadConfirm = "";
		if ($rr->status and $rr->field3 and ($t->type == "avdesk" or $t->type == "avdesk6")) {
			$download="<a class=rootlink href='$rr->field3'><img src=./_rootimages/download.gif style='padding-right: 5px' border=0 alt='".$_lang[ShopDownloadDist]."'>$_lang[ShopDownloadDist]</a><BR>";
		} else if ($rr->status and $t->type == "soft") {
			if ($t->field2) {
				$linkDaysLeft = $t->field2 - $rr->daysFromBuy;
				if ($linkDaysLeft < 0) { $linkDaysLeft = 0; }
				$downloadConfirm .= $_lang[ShopDownloadLinkDays].": $linkDaysLeft / $t->field2\\n";
			} 
			if ($t->field3) {
				$downloadConfirm .= $_lang[ShopDownloadLinkCount].": $rr->field1/$t->field3\\n";
			}
			if ($downloadConfirm) { $downloadConfirm .= "\\n".$_lang[ShopDownloadLinkConfirm]; }

			$download="<a class=rootlink href=?do=download&type=shop&id=$rr->id onclick=\"javascript: return confirm('$downloadConfirm');\"><img src=./_rootimages/download.gif style='padding-right: 5px' border=0 alt='".$_lang[ShopDownloadFile]."'>$_lang[ShopDownloadFile]</a><BR>";
		} else {
			$download="";
		}

		if ($rr->status and $rr->field3 and ($t->type == "ispmanagerlite" or $t->type == "ispmanagerlitefull" or $t->type == "ispmanagerlitetrial" or $t->type == "ispmanagerpro" or $t->type == "ispmanagerprofull" or $t->type == "ispmanagerprotrial" or $t->type == "vdsmanagerlinux" or $t->type == "vdsmanagerlinuxfull" or $t->type == "vdsmanagerfreebsd" or $t->type == "vdsmanagerfreebsdfull" or $t->type == "billmanagerstandart" or $t->type == "billmanageradvanced" or $t->type == "billmanagercorporate" or $t->type == "dsmanager" or $t->type == "dsmanagerfull" or $t->type == "dnsmanagerfull" or $t->type == "ipmanagerfull")) {
			$licenseName="<A class=rootlink href=?do=$do&sub=licenseUpdate&type=name&id=$rr->id><img src=./_rootimages/dns.gif style='padding-right: 5px' border=0 alt='".$_lang[ShopChangeLicenseName]."'>$_lang[ShopChangeLicenseName]</a><BR>";
		} else {
			$licenseName="";
		}

		if ($rr->status and $rr->field3 and ($t->type == "ispmanagerlite" or $t->type == "ispmanagerlitefull" or $t->type == "ispmanagerlitetrial" or $t->type == "ispmanagerpro" or $t->type == "ispmanagerprofull" or $t->type == "ispmanagerprotrial" or $t->type == "vdsmanagerlinux" or $t->type == "vdsmanagerlinuxfull" or $t->type == "vdsmanagerfreebsd" or $t->type == "vdsmanagerfreebsdfull" or $t->type == "billmanagerstandart" or $t->type == "billmanageradvanced" or $t->type == "billmanagercorporate" or $t->type == "dsmanager" or $t->type == "dsmanagerfull" or $t->type == "dnsmanagerfull" or $t->type == "ipmanagerfull")) {
			$licenseIP="<A class=rootlink href=?do=$do&sub=licenseUpdate&type=ip&id=$rr->id><img src=./_rootimages/dns.gif style='padding-right: 5px' border=0 alt='".$_lang[ShopChangeLicenseIP]."'>$_lang[ShopChangeLicenseIP]</a><BR>";
			$textAfterName = "<BR>[".$rr->field2."]";
		} else {
			$licenseIP="";
			$textAfterName = "";
		}

                $statusShop="<img src=./_rootimages/obrabotan_".$rr->status."_small.gif border=0 alt='".$_status[$rr->status]."' title='".$_status[$rr->status]."'>";

		print "
		<tr class=$font_row height=30>
		<td align=center>$rr->id</td>
		<td align=center>".mydate($rr->orderdate)."</td>
		<td align=center><b>$t->name</b>$textAfterName</td>
		<td align=center>$todate</td>
		<td align=center nowrap>$leftDays</td>
		<td align=center>$statusShop</td>
		<td align=left valign=middle>$licenseName$licenseIP$download$remark$dogovor<A class=rootlink href=?do=bills&param=shop_id&search=$rr->id><img src=./_rootimages/bills.gif style='padding-right: 5px' border=0 alt='".$_lang[BillsTitle].": $bills'>$_lang[BillsTitle]: $bills</a><BR>$renew$delete</td>
		</tr>
		";

		$cnt++;
	}
	?>
        <tr class=<? print $font_head?>><Td colspan=7><? print $_lang[ShopTotalOrders]?>: <? print $rows?>, <? print $_lang[ShopOrdersOnPage]?>: <? print $cnt?></td></tr>
        <tr><td colspan=7 align=right><? print $txt?></td></tr>
	</table>
	<?
	foot('utf-8');
}

if ($do == "bills") {
	$allowAttachBills = GetSetting("allowAttachBills");

	if ($sub == "getFaktura" and $id) {
		$bill = GetBillById($id);

		if ($bill->id and $_SESSION["userId"] == $bill->uid and $bill->status == "1") {
			$user = GetUserById($bill->uid);
			$profile = GetUserProfileByUserId($bill->uid);

			if (($profile->org == "3" and $profile->firma and $profile->phone) or ($profile->org == "2" and $profile->name and $profile->surname and $profile->phone)) {
				createFaktura('', $bill->id, 1, '', '', 1);
				exit;
			}
		}
	}

        head('utf-8',$_lang[BillsTitle]);
	print "<H1 class=pagetitle>".$_lang[BillsTitle]."</H1><hr class=hr>";

        if ($sub == 'delete' and $id) {
		$bill = GetBillById($id);
		if ($bill->id and $_SESSION["userId"] == $bill->uid) {
			if ($bill->host_id) { $t=GetOrderById($bill->host_id); }
			if ($bill->domain_id) { $d=GetDomainById($bill->domain_id); }
			if ($bill->shop_id) { $s=GetOrderShopById($bill->shop_id); }

			if (!$bill->addfunds and $bill->money) {
			} else if ($t->id and $t->startdate == "0000-00-00" and $t->todate == "0000-00-00") {
			} else if ($t->id and $t->testPeriod) {
			} else if ($d->id and $d->startdate == "0000-00-00" and $d->todate == "0000-00-00") {
			} else if ($s->id and $s->startdate == "0000-00-00" and $s->todate == "0000-00-00") {
			} else  {
				if (!$bill->status) {
			                @mysql_query("delete from bills where id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				} else {
			                @mysql_query("update bills set archived=1 where id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				}
	       		        print $_lang[BillsDeleteSuccess]."<BR><BR>";
			}
		}
        }

	if ($sub == "attach" and $allowAttachBills and @count($billsToAttach) > 1) {
                @mysql_query("insert into bills (uid,isMainAttach,created) VALUES('".$_SESSION["userId"]."',1,NOW())") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		$mainBill = @mysql_insert_id();

		if ($mainBill) {
			while (list($k,$v) = @each($billsToAttach)) {
		                @mysql_query("update bills set attachTo='$mainBill' where id='$v' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			}
		}
	}

	if ($sub == "unattach" and $allowAttachBills and $id) {
		$bill = GetBillById($id);
		if ($bill->id and $bill->isMainAttach and !$bill->status) {
	                @mysql_query("update bills set isMainAttach=0 where id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

			if (!$bill->host_id and !$bill->domain_id and !$bill->shop_id and !$bill->money) {
		                @mysql_query("delete from bills where id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			}

	                @mysql_query("update bills set attachTo='' where attachTo='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		}

	}

        if ($search and ($param == "host_id" or $param == "domain_id" or $param == "shop_id")) { 
       		$where = " and $param='$search'";
        } else {
		$where = "";
	}

        getfont();

        $r=@mysql_query("select * from bills WHERE archived=0 and uid='".$_SESSION["userId"]."' $where order by id desc") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
        $rows = mysql_num_rows($r);
        list($start, $perPage, $txt) = MakePages($page, $rows);

	if ($allowAttachBills) {
        	?>
		<form method=post>
		<input type=hidden name=do value=<? print $do?>>
		<input type=hidden name=sub value=attach>
		<?
	}
	?>
	<script type="text/javascript">
	function setChecked(obj)
	{
		var check = document.getElementsByName("billsToAttach[]");
		for (var i=0; i<check.length; i++)
		{
			check[i].checked = obj.checked;
		}
	}
	</script>

        <table class='rpTable' cellpadding=3>
        <tr><td colspan=9 align=right><? print $txt?></td></tr>
        <tr class=<? print $font_head?> align=center><td><? if ($allowAttachBills) { print "<input type=checkbox onclick='setChecked(this)'>"; } ?></td><td><? print $_lang[BillsDate]?></td><td><? print $_lang[BillsBillNo]?></td><td><? print $_lang[BillsTarif]?></td><td><? print $_lang[BillsDomain]?></td><td><? print $_lang[BillsCost]?>, <? print CURS?></td><td><? print $_lang[BillsSumma]?>, <? print CURS?></td><td><? print $_lang[BillsStatus]?></td><td></td></tr>
        <?

        $r=@mysql_query("select * from bills where archived=0 and uid='".$_SESSION["userId"]."' $where order by id desc LIMIT $start,$perPage") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
        $cnt=0;
        getfont();
        while ($rr = @mysql_fetch_object($r)) {
		$tarifTxt = "";
		$domainTxt = "";
		$cost = "";

		$t=@mysql_query("select t1.*, t2.name, t2.vid from orders as t1, tarifs as t2 where t1.archived=0 and t1.id='$rr->host_id' and t1.uid='".$_SESSION["userId"]."' and t1.tarif=t2.id");
		if (mysql_num_rows($t) > 0) {
			$t=mysql_fetch_object($t);
			if (!$rr->newaddons) {$tVid=$t->vid; $cost = "<img src=./_rootimages/hosting.gif border=0 alt='".$_lang[OrderType][$tVid]."'> ".round($rr->money_host*CURK,2); }
			$newreg='';
			$domain_srok='';

			if ($rr->tarif) {
				$curTarif = GetTarifById($rr->tarif);
				if ($curTarif->id) {
					$t->name = $curTarif->name;
				} else {
					$t->name = "UNKNOWN";
				}
			}

			if ($rr->server) {
				$curServer = GetServers($rr->server);
				if ($curServer->id) {
					$curServer->place = $curServer->place;
				} else {
					$curServer->place = "UNKNOWN";
				}
			}

			if ($rr->newtarif) {
				$newTarif=GetTarifById($rr->newtarif);
				$tarifTxt = "<B>$t->name</b> => <B>".$newTarif->name."</b><BR>[".$_lang[BillsChangeTarif]."]";
			} else if ($rr->newserver) {
				$newServer=GetServers($rr->newserver);
				$tarifTxt = "<B>$t->name</b><BR><B>".$curServer->place."</b> => <B>".$newServer->place."</b><BR>[".$_lang[BillsChangeServer]."]";
			} else if ($rr->newslots) {
				$tarifTxt = "<B>$t->name</b><BR><B>".$t->slots."</b> => <B>".$rr->newslots."</b><BR>[".$_lang[BillsChangeSlots]."]";
			} else if ($rr->newaddons) {
				$newaddons = GetAddonsIdsByTxt($rr->newaddons);
				$newaddonsTxt = "";
				while (list($k,$v) = each($newaddons)) {
					$oneAddon = GetAddonById($v);
					if ($oneAddon->id) {
						$newaddonsTxt = $newaddonsTxt."- $oneAddon->name<BR>";
					}
				}

				$tarifTxt = "<B>".$t->name."</b> <img src=./_rootimages/question.gif border=0 alt='<B>".$_lang[BillsOrderedAddons].":</b><BR>$newaddonsTxt'><BR>[".$_lang[BilldOrderAddons]."]";
			} else {
				$tarifTxt = "<B>".$t->name."</b><BR>[".$rr->host_srok." ".$_lang[OrderSokraschenieMonth]."]";
			}

			if ($t->domain_reg == "1" or $t->domain_reg == "3") {
				if ($rr->renew) {
					if ($rr->domain_id) {
						$newreg=$_renewmin[1];
						$domain_srok=" [$rr->domain_srok ".$_lang[OrderSokraschenieMonth]."]";
					} else {
						$newreg=$_renewmin[0];
					}
				} else if ($rr->transfer) {
					$newreg=$_newregmin[3];
					$domain_srok=" [$rr->domain_srok ".$_lang[OrderSokraschenieMonth]."]";
				} else {
					$newreg=$_newregmin[1];
       		                        $domain_srok=" [$rr->domain_srok ".$_lang[OrderSokraschenieMonth]."]";
				}
			} else if ($t->domain_reg == "2") {
				$newreg='';
			} else if ($t->domain_reg == "0") {
				if ($rr->renew or $rr->newtarif or $rr->newserver or $rr->newslots) {
					$newreg='';
				} else {
					$newreg=$_newregmin[0];
				}
			}
			if ($newreg) {$newreg="[".$newreg."]";}

			if ($t->domain and !$rr->newaddons) { $domainTxt = "<B>".$t->domain."</b><BR>".$newreg.$domain_srok; }
			else if ($t->domain and $rr->newaddons) {  $domainTxt = "<B>".$t->domain."</b><BR>&nbsp;"; }
			else { $domainTxt = ""; }

		}

		$d=@mysql_query("select * from orders_domains domains where id='$rr->domain_id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		if (mysql_num_rows($d) > 0) {
			$d = mysql_fetch_object($d);
			if (!$rr->privacy) { $cost = $cost." <img src=./_rootimages/domain.gif border=0 alt='".$_lang[Domain]."'> ".round($rr->money_domain*CURK,2); }

			if (!$rr->host_id) {
				if ($rr->privacy) {
					$domainTxt = "<B>".$d->domain."</b> <img src=./_rootimages/question.gif border=0 alt='<B>".$_lang[BillsOrderedAddons].":</b><BR>".$_lang[OrderPrivacy]."'><BR>[".$_lang[BilldOrderAddons]."]";
				}
				else {
					$domainTxt = "<B>".$d->domain."</b><BR>";
					if ($rr->renew) { $domainTxt .= "[".$_lang[DomainRenewMin]."] "; } else { $domainTxt .= "[".$_lang[DomainNewMin]."] "; }
					$domainTxt .= "[$rr->domain_srok ".$_lang[OrderSokraschenieMonth]."]";
				}
			}
		}

		$s=@mysql_query("select * from orders_shop where id='$rr->shop_id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		if (mysql_num_rows($s) > 0) {
			$s = mysql_fetch_object($s);
			$cost = $cost." <img src=./_rootimages/shop.gif border=0 alt='".$_lang[OrderItem]."'> ".round($rr->money_shop*CURK,2);

			$t=GetShopItemById($s->item);

			$shopTxt = "<B>".$t->name."</b><BR>";
			if ($rr->renew) { $shopTxt .= "[".$_lang[DomainRenewMin]."] "; } else { $shopTxt .= "[".$_lang[DomainNewMin]."] "; }
			if ($t->costtype != "one") { $shopTxt .= "[$rr->shop_srok ".$_lang[OrderSokraschenieMonth]."]"; }
		}

		if ($rr->money_addons) { $cost = $cost." <img src=./_rootimages/addons.gif border=0 alt='".$_lang[OrderAddons]."'> ".round($rr->money_addons*CURK,2);}

		$u=@mysql_query("select * from users where id='$rr->uid'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		$u=mysql_fetch_object($u);

		$profile = GetUserProfileByUserId($rr->uid);

		if (!$rr->status) {
			if (!$rr->addfunds and $allowAttachBills) {
				if ($rr->isMainAttach) {
					$attachBox = "<a href=?do=$do&sub=unattach&id=$rr->id><img src='./_rootimages/attach.gif' border=0 alt='".$_lang[BillsBillsUnAttach]."'><img src='./_rootimages/attach.gif' border=0 alt='".$_lang[BillsBillsUnAttach]."'><img src='./_rootimages/attach.gif' border=0 alt='".$_lang[BillsBillsUnAttach]."'></a>";
				} else if ($rr->attachTo) {
					$attachBox = "<img src='./_rootimages/attach.gif' border=0 alt='".$_lang[BillsBillsAttachTo]." ".sprintf("%04d", $rr->attachTo)."'>";
				} else {
					$attachBox = "<input type=checkbox name=billsToAttach[] value=$rr->id>";	
				}
			} else {
				$attachBox = "";
			}
		} else {
			if ($rr->isMainAttach) {
				$attachBox = "<img src='./_rootimages/attach.gif' border=0 alt='".$_lang[BillsBillsIsMainAttach]."'><img src='./_rootimages/attach.gif' border=0 alt='".$_lang[BillsBillsIsMainAttach]."'><img src='./_rootimages/attach.gif' border=0 alt='".$_lang[BillsBillsIsMainAttach]."'></a>";
			} else if ($rr->attachTo) {
				$attachBox = "<img src='./_rootimages/attach.gif' border=0 alt='".$_lang[BillsBillsAttachTo]." ".sprintf("%04d", $rr->attachTo)."'>";
			} else {
				$attachBox = "";
			}
		}

		if ($rr->status != 0) {
			$statusAddOn=" ".mydate($rr->payed); 

			$f = @mysql_query("select * from kvitancii_faktura where active='1' and enableSchetFaktura='1'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>Function: ".__FUNCTION__."<BR>MySQL Error: ".mysql_error());

			if (@mysql_num_rows($f) > 0 and (($profile->org == "3" and $profile->firma and $profile->phone) or ($profile->org == "2" and $profile->name and $profile->surname and $profile->phone))) {
				$make="<a class=rootlink href=?do=bills&sub=getFaktura&id=$rr->id><img src=./_rootimages/schet.gif border=0 style='padding-right: 5px' alt='".$_lang[BillsGetFaktura]."'>$_lang[BillsGetFaktura]</a><BR>";
			} else {
				$make='';
			}
		} else {
			$statusAddOn=""; 

			if (!$rr->attachTo) {
				$make="<a class=rootlink href=?do=pay&id=$rr->id><img src=./_rootimages/pay.gif border=0 style='padding-right: 5px' alt='".$_lang[BillsPayBill]."'>$_lang[BillsPayBill]</a><BR>";

				if (!$rr->addfunds and $rr->money) {
				} else if ($t->id and $t->startdate == "0000-00-00" and $t->todate == "0000-00-00") {
				} else if ($t->id and $t->testPeriod) {
				} else if ($d->id and $d->startdate == "0000-00-00" and $d->todate == "0000-00-00") {
				} else if ($s->id and $s->startdate == "0000-00-00" and $s->todate == "0000-00-00") {
				} else if (!$rr->isMainAttach) {
					$make=$make."<a class=rootlink href=?do=$do&sub=delete&id=$rr->id onclick=\"javascript: return confirm('".$_lang[BillsDeleteBillAlert]."');\"><img src=./_rootimages/del.gif style='padding-right: 10px' border=0 alt='".$_lang[BillsDeleteBill]."'>$_lang[BillsDeleteBill]</a><BR>";
				}
			} else {
				$make = "";
			}
		}

		getfont();
		?>
		<tr class="<? print $font_row?>" height=30>
		<td align=center><? print $attachBox?></td>
		<td align=center><? print mydate($rr->created)?></td>
		<td align=center><B><? print sprintf("%04d", $rr->id)?></b></td>
	<? if ($rr->isMainAttach and !$rr->host_id and !$rr->domain_id and !$rr->shop_id and !$rr->money and !$rr->addfunds) { ?>
		<td align=center colspan=3><? print $_lang[BillsBillByAttach]?></td>

		<?
                $attbs = @mysql_query("select * from  bills where attachTo='$rr->id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		while ($attb = @mysql_fetch_object($attbs)) {
			$rr->money_host = $rr->money_host + $attb->money_host;
			$rr->money_domain = $rr->money_domain + $attb->money_domain;
			$rr->money_addons = $rr->money_addons + $attb->money_addons;
			$rr->money = $rr->money + $attb->money;
			$rr->money_shop = $rr->money_shop + $attb->money_shop;
		}
		?>
	<? } else if (!$rr->addfunds and !$rr->money and !$rr->shop_id) { ?>
		<td><? print $tarifTxt?></td>
		<td><? print $domainTxt?></td>
		<td align=center><? print $cost?></td>
	<? } else if (!$rr->addfunds and $rr->money) { ?>
		<td align=center colspan=3><? print $_lang[BillsBillByAdmin]?> <? if ($rr->comment) { print "<img src=./_rootimages/question.gif border=0 alt='<B>".$_lang[OrderComment].":</b><BR>$rr->comment'>"; } ?></td>
	<? } else if ($rr->shop_id) { ?>
		<td colspan=2><? print $_lang[OrderItem]?>: <? print $shopTxt?></td>
		<td align=center><? print $cost?></td>
	<? } else { ?>
		<td align=center colspan=3><? print $_lang[BillsAddFunds]?></td>
	<? } ?>
		<td align=center><B><? print round(($rr->money_host+$rr->money_domain+$rr->money_addons+$rr->money+$rr->money_shop)*CURK,2)?></b></td>

		<? if ($rr->status == "2" and $rr->payed != "0000-00-00") { $rr->status = 1; } ?>
		<td align=center><img src=./_rootimages/payed_<? print $rr->status?>_small.gif border=0 alt="<? print $_statusBill[$rr->status].$statusAddOn?>"></td>
		<td><? print $make?></td>
		</tr>
		<?
		$cnt++;
	}
	?>
        <tr class=<? print $font_head?>><Td colspan=9><? print $_lang[BillsTotalBills]?>: <? print $rows?>, <? print $_lang[BillsBillsPerPage]?>: <? print $cnt?></td></tr>
        <tr><td colspan=9 align=right><? print $txt?></td></tr>

	</table><BR>

	<? if ($allowAttachBills) { ?>
		<? print $_lang[BillsSelectedBills]?>: <input type=submit value='<? print $_lang[BillsBillsAttach]?>' class=button>
		</form>
	<? } ?>
	<?
	foot('utf-8');
}

if ($do == "pay" and $id) {

	if (!$workWithoutAuth) { $sqlAddon = "and uid='".$_SESSION["userId"]."'"; }

        $r=@mysql_query("select * from bills where archived=0 and id='$id' $sqlAddon") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

        if (mysql_num_rows($r) > 0) {
                $r=mysql_fetch_object($r);
                $sid=sprintf("%04d", $r->id);
		$money=$r->money_host+$r->money_domain+$r->money_addons+$r->money+$r->money_shop;

                if (!$workWithoutAuth and $testPeriod and $testPeriodHostId and GetSetting("testPeriodEnable")) {
			if (GetSetting("testPeriodAutoCreate")) {
				if (GetSetting("orderProcessTypeHost")) {
					AddBillToQueue($r->id, 1);
				} else {
					if (createUser($testPeriodHostId)) {
						$testPeriodCreated = 1;
					}
				}
			}
		}

		if ($r->isMainAttach) {
		        $q=@mysql_query("select * from bills where attachTo='$id' $sqlAddon") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			if (mysql_num_rows($q) > 0) {
				while ($qq = @mysql_fetch_object($q)) {
					$sids[] = sprintf("%04d", $qq->id);
					$money = $money + $qq->money_host+$qq->money_domain+$qq->money_addons+$qq->money+$qq->money_shop;
				}
				$sid = $sid.": ".@join(",",$sids);
			}
		}

		if ($sub == "kvitanciya" and $kvid) {
			print createKvitanciya($kvid, $id, 0);

			mclose();
			exit;

		}
		else if ($sub == "kvitanciyapdf" and $kvid) {
			createKvitanciya($kvid, $id, 1);

			mclose();
			exit;
		}
		else if ($sub == "faktura" and $fid and !$workWithoutAuth) {

			$profile = GetUserProfileByUserId($_SESSION["userId"]);
			if ($profile->org == "3" and $profile->firma and $profile->phone) {
				print createFaktura($fid, $id, 0);
			} else if (($profile->org == "2" or $profile->org == "1") and $profile->name and $profile->surname and $profile->phone) {
				print createFaktura($fid, $id, 0);
			} else if ($profile->org == "3") {
				error($_lang[PayErrorNoOrgOrPhoneInProfile]);
			} else if ($profile->org == "2" or $profile->org == "1" or !$profile->org) {
				error($_lang[PayErrorNoNameOrPhoneInProfile]);
			}
			mclose();
			exit;
		}
		else if ($sub == "fakturapdf" and $fid and !$workWithoutAuth) {
			$profile = GetUserProfileByUserId($_SESSION["userId"]);

			if ($profile->org == "3" and $profile->firma and $profile->phone) {
				createFaktura($fid, $id, 1);
			} else if (($profile->org == "2" or $profile->org == "1") and $profile->name and $profile->surname and $profile->phone) {
				createFaktura($fid, $id, 1);
			} else if ($profile->org == "3") {
				error($_lang[PayErrorNoOrgOrPhoneInProfile]);
			} else if ($profile->org == "2" or $profile->org == "1" or !$profile->org) {
				error($_lang[PayErrorNoNameOrPhoneInProfile]);
			}
			mclose();
			exit;
		}


                head('utf-8',$_lang[PayTitle]. $sid);

		print "<H1 class=pagetitle>".$_lang[PayTitle]." $sid</H1><hr class=hr>";

		if ($r->status) {print "Счет уже оплачен.<br><br>";}
		else if ($money == 0) {
			if ($r->domain_id) {
				$zone = GetZoneByDomainOrderId($r->domain_id);
				$domain = GetDomainById($r->domain_id);
				if (preg_match("/\.{0,1}[ru|su|рф]$/ui",$zone->zone)) {
					$isR = 1;
					if (!checkProfile("max", $_SESSION["userId"], $domain->profileId)) {
						print $_lang[PayErrorNoProfileWithPassport];
						print "<BR>".$_lang[PayGoto]." <A class=rootlink href=billing.php?do=profile&isR=1&profileId=$domain->profileId&bill_id=$r->id>".$_lang[PayGotoFor]."</a>.";
						foot('utf-8');
						mclose();
						exit;
					}
				} else {
					$isD = 1;
					if (mb_strtolower($zone->zone) == "pp.ua") { $isPPUA = 1; $rF[] = "mobile"; }

					if (!checkProfile("min", $_SESSION["userId"], $domain->profileId)) {
						print $_lang[PayErrorNoProfile];
						print "<BR>".$_lang[PayGoto]." <A class=rootlink href=billing.php?do=profile&isD=1&isPPUA=$isPPUA&profileId=$domain->profileId&bill_id=$r->id>".$_lang[PayGotoFor]."</a>.";
						foot('utf-8');
						mclose();
						exit;
					}
				}

				if (mb_strtolower($zone->zone) == "ru") {
					$isR = 1; $isD = "";
					if (!checkProfileByAdmin($_SESSION["userId"], $domain->profileId)) {
						print $_lang[PayErrorNoProfileCheck];
						foot('utf-8');
						mclose();
						exit;
					}
				} else {
					$isD = 1; $isR = "";
					if (!checkProfileByAdmin($_SESSION["userId"], $domain->profileId)) {
						print $_lang[PayErrorNoProfileCheck];
						foot('utf-8');
						mclose();
						exit;
					}
				}
			}

			MakeBillPayed($r->id,1,$_lang[PayNullBill]);
			print $_lang[PayBillPaySuccess]."<BR><BR>";
		}
		else {
			if ($r->domain_id) {
				$zone = GetZoneByDomainOrderId($r->domain_id);
				$domain = GetDomainById($r->domain_id);
				if (preg_match("/\.{0,1}[ru|su|рф]$/ui",$zone->zone)) {
					$isR = 1;
					if (!checkProfile("max", $_SESSION["userId"], $domain->profileId)) {
						print $_lang[PayErrorNoProfileWithPassport];
						print "<BR>".$_lang[PayGoto]." <A class=rootlink href=billing.php?do=profile&isR=1&profileId=$domain->profileId&bill_id=$r->id>".$_lang[PayGotoFor]."</a>.";
						foot('utf-8');
						mclose();
						exit;
					}
				} else {
					$isD = 1;
					if (mb_strtolower($zone->zone) == "pp.ua") { $isPPUA = 1; $rF[] = "mobile"; }

					if (!checkProfile("min", $_SESSION["userId"], $domain->profileId)) {
						print $_lang[PayErrorNoProfile];
						print "<BR>".$_lang[PayGoto]." <A class=rootlink href=billing.php?do=profile&isD=1&isPPUA=$isPPUA&profileId=$domain->profileId&bill_id=$r->id>".$_lang[PayGotoFor]."</a>.";
						foot('utf-8');
						mclose();
						exit;
					}
				}

				if (mb_strtolower($zone->zone) == "ru") {
					$isR = 1; $isD = "";
					if (!checkProfileByAdmin($_SESSION["userId"], $domain->profileId)) {
						print $_lang[PayErrorNoProfileCheck];
						foot('utf-8');
						mclose();
						exit;
					}
				} else {
					$isD = 1; $isR = "";
					if (!checkProfileByAdmin($_SESSION["userId"], $domain->profileId)) {
						print $_lang[PayErrorNoProfileCheck];
						foot('utf-8');
						mclose();
						exit;
					}
				}

			}

	                if ($fromreg) {
				print $_lang[PayOrderCreateSuccess]."<BR>";

			}
			if ($testPeriod and $testPeriodCreated) {
				print $_lang[PayOrderTestCreateSuccess]."<BR>";
			} else if ($testPeriod and !$testPeriodCreated) {
				print $_lang[PayOrderTestCreateLater]."<BR>";
			}

			if ($sub == "partnerPay" and !$workWithoutAuth) {
				if (GetSetting("partnerEnable") and GetSetting("partnerEnablePayOrders")) {
					$partnerMoney=GetUserPartnerMoney($_SESSION["userId"]);
					$money=floatval($money);

					if ($partnerMoney >= $money) {
						@mysql_query("update users set partnerMoney=partnerMoney-$money where id='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
						MakeBillPayed($r->id,1,$_lang[payPartner]);
						print $_lang[PayBillPaySuccess]."<br><br>";
					}
					else {$sub='';print "<font color=red>".$_lang[PayErrorNoMoneyOnPartner]."</font><BR><BR>";}
		
				} else {$sub='';print "<font color=red>".$_lang[PayErrorPartnerOff]."</font><BR><BR>";}
			}

			if ($sub == "balancePay" and !$workWithoutAuth) {
				$balanceMoney=GetUserMoney($_SESSION["userId"]);
				$money=floatval($money);

				if ($balanceMoney >= $money) {
					@mysql_query("update users set money=money-$money where id='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
					MakeBillPayed($r->id,1,$_lang[payBalance]);
					print $_lang[PayBillPaySuccess]."<br><br>";
				}
				else {$sub='';print "<font color=red>".$_lang[PayErrorNoMoneyOnBalance]."</font><BR><BR>";}
			}

	                if (!$sub or $sub == "gotomerchant") {

			if ($fromchange) {$textaddon = $_lang[PayForTarifChange]." ";}
			else if ($fromchangeserver) {$textaddon = $_lang[PayForServerChange]." ";}
			else if ($fromchangeslots) {$textaddon = $_lang[PayForSlotsChange]." ";}

			if (!$check) { print "<font color=red>".$textaddon.$_lang[PayNeedBillPay]."</font><BR><BR>"; }

	                $manager_email=GetSetting("manager_email");
	      	        $company_name=htmlEncode(GetSetting("company_name"));
	                $payment_url=GetSetting("payment_url");
	                $nopayment_url=GetSetting("nopayment_url");

			if ($workWithoutAuth) {
				$user = GetUserById($r->uid);
			} else {
				$user = GetUserById($_SESSION["userId"]);
			}

			$money_usd = $money*GetCurrencyKoeficientByCode("USD");
			$money_rub = $money*GetCurrencyKoeficientByCode("RUB");
			$money_uah = $money*GetCurrencyKoeficientByCode("UAH");
			$money_eur = $money*GetCurrencyKoeficientByCode("EUR");
			$money_byr = $money*GetCurrencyKoeficientByCode("BYR");
			$money_kzt = $money*GetCurrencyKoeficientByCode("KZT");
			$money_uzs = $money*GetCurrencyKoeficientByCode("UZS");

			if ($sub == "gotomerchant" and $paytype and $paymentId) {
				$payy = GetPaymentSystemById($paymentId);

				if ($payy->type != "easypay" and $payy->type != "netcard" and $payy->type != "netmoney" and !$check) { print $_lang[PayForPayGoto]."<BR><BR>"; }

				if ($payy->type == "webmoney" and $paytype == "wmz") {
					$money_wmz = $money_usd + ($money_usd/100)*$payy->small1; $money_wmz = round($money_wmz,2);

			                $wmz=$payy->text1;
			                $wmsecret=decodePwd($payy->pass1);
					if ($wmz and $wmsecret) { 
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_wmz',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

						<B><? print $_lang[PayWebMoneyRisks]?></b><BR><BR>
						<? print $_lang[PayWebMoneyAlert]?><BR><BR>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://merchant.webmoney.ru/lmi/payment.asp" accept-charset="windows-1251">
			                        <input type=hidden name=LMI_PAYMENT_DESC value="<? print $company_name?>: bill <? print $sid?>">
			                        <input type=hidden name=LMI_PAYEE_PURSE value="<? print $wmz?>">
			                        <input type=hidden name=LMI_PAYMENT_AMOUNT value="<? print $money_wmz?>">
						<input type=hidden name=LMI_SUCCESS_URL value="<? print $payment_url?>">
						<input type=hidden name=LMI_FAIL_URL value="<? print $nopayment_url?>">
						<input type=hidden name=BILL_ID value="<? print $r->id?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_wmz?> WMZ">
			                        </form>
					<? }
				}
				if ($payy->type == "webmoney" and $paytype == "wmr") {
					$money_wmr = $money_rub + ($money_rub/100)*$payy->small2; $money_wmr = round($money_wmr,2);

			                $wmr=$payy->text2;
			                $wmsecret=decodePwd($payy->pass1);
					if ($wmr and $wmsecret) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_wmr',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); 

						if ($paysubtype == "card") { $urlAddon = "?at=authtype_16"; } else { $urlAddon = ""; }
						?>

						<B><? print $_lang[PayWebMoneyRisks]?></b><BR><BR>
						<? print $_lang[PayWebMoneyAlert]?><BR><BR>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://merchant.webmoney.ru/lmi/payment.asp<? print $urlAddon?>" accept-charset="windows-1251">
			                        <input type=hidden name=LMI_PAYMENT_DESC value="<? print $company_name?>: bill <? print $sid?>">
			                        <input type=hidden name=LMI_PAYEE_PURSE value="<? print $wmr?>">
			                        <input type=hidden name=LMI_PAYMENT_AMOUNT value="<? print $money_wmr?>">
						<input type=hidden name=LMI_SUCCESS_URL value="<? print $payment_url?>">
						<input type=hidden name=LMI_FAIL_URL value="<? print $nopayment_url?>">
						<input type=hidden name=BILL_ID value="<? print $r->id?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_wmr?> WMR">
			                        </form>
					<? }
				}
				if ($payy->type == "webmoney" and $paytype == "wme") {
					$money_wme = $money_eur + ($money_eur/100)*$payy->small4; $money_wme = round($money_wme,2);

			                $wme=$payy->text4;
			                $wmsecret=decodePwd($payy->pass1);
					if ($wme and $wmsecret) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_wme',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

						<B><? print $_lang[PayWebMoneyRisks]?></b><BR><BR>
						<? print $_lang[PayWebMoneyAlert]?><BR><BR>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://merchant.webmoney.ru/lmi/payment.asp" accept-charset="windows-1251">
			                        <input type=hidden name=LMI_PAYMENT_DESC value="<? print $company_name?>: bill <? print $sid?>">
			                        <input type=hidden name=LMI_PAYEE_PURSE value="<? print $wme?>">
			                        <input type=hidden name=LMI_PAYMENT_AMOUNT value="<? print $money_wme?>">
						<input type=hidden name=LMI_SUCCESS_URL value="<? print $payment_url?>">
						<input type=hidden name=LMI_FAIL_URL value="<? print $nopayment_url?>">
						<input type=hidden name=BILL_ID value="<? print $r->id?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_wme?> WME">
			                        </form>
					<? }
				}
				if ($payy->type == "webmoney" and $paytype == "wmu") {
					$money_wmu = $money_uah + ($money_uah/100)*$payy->small3; $money_wmu = round($money_wmu,2);

			                $wmu=$payy->text3;
			                $wmsecret=decodePwd($payy->pass1);
					if ($wmu and $wmsecret) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_wmu',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

						<B><? print $_lang[PayWebMoneyRisks]?></b><BR><BR>
						<? print $_lang[PayWebMoneyAlert]?><BR><BR>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://merchant.webmoney.ru/lmi/payment.asp" accept-charset="windows-1251">
			                        <input type=hidden name=LMI_PAYMENT_DESC value="<? print $company_name?>: bill <? print $sid?>">
			                        <input type=hidden name=LMI_PAYEE_PURSE value="<? print $wmu?>">
			                        <input type=hidden name=LMI_PAYMENT_AMOUNT value="<? print $money_wmu?>">
						<input type=hidden name=LMI_SUCCESS_URL value="<? print $payment_url?>">
						<input type=hidden name=LMI_FAIL_URL value="<? print $nopayment_url?>">
						<input type=hidden name=BILL_ID value="<? print $r->id?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_wmu?> WMU">
			                        </form>
					<? }
				}
				if ($payy->type == "webmoney" and $paytype == "wmb") {
					$money_wmb = $money_byr + ($money_byr/100)*$payy->small5; $money_wmb = round($money_wmb,2);

			                $wmb=$payy->text5;
			                $wmsecret=decodePwd($payy->pass1);
					if ($wmb and $wmsecret) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_wmb',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

						<B><? print $_lang[PayWebMoneyRisks]?></b><BR><BR>
						<? print $_lang[PayWebMoneyAlert]?><BR><BR>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://merchant.webmoney.ru/lmi/payment.asp" accept-charset="windows-1251">
			                        <input type=hidden name=LMI_PAYMENT_DESC value="<? print $company_name?>: bill <? print $sid?>">
			                        <input type=hidden name=LMI_PAYEE_PURSE value="<? print $wmb?>">
			                        <input type=hidden name=LMI_PAYMENT_AMOUNT value="<? print $money_wmb?>">
						<input type=hidden name=LMI_SUCCESS_URL value="<? print $payment_url?>">
						<input type=hidden name=LMI_FAIL_URL value="<? print $nopayment_url?>">
						<input type=hidden name=BILL_ID value="<? print $r->id?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_wmb?> WMB">
			                        </form>
					<? }
				}
				if ($payy->type == "yandex") {
					$money_yandex = $money_rub + ($money_rub/100)*$payy->small1; $money_yandex = round($money_yandex,2);
					$money_yandex_k = $money_yandex - $money_yandex*0.005;

			                $yandex=$payy->text1;
					if ($yandex) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_yandex',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form method="POST" name="mform" target="_blank" action="https://money.yandex.ru/direct-payment.xml" accept-charset="windows-1251">
						<input type="hidden" name="rnd" value="<? print rand(10000000,99999999)?>">
						<input type="hidden" name="scid" value="767">
						<input type="hidden" name="shn" value="Перевод на e-mail/счет">
						<input type="hidden" name="targetcurrency" value="643">
						<input type="hidden" name="SuccessTemplate" value="ym2xmlsuccess">
						<input type="hidden" name="ErrorTemplate" value="ym2xmlerror">
						<input type="hidden" name="ShowCaseID" value="7">
						<input type="hidden" name="isViaWeb" value="true">
						<input type="hidden" name="short-dest" value="<? print $company_name?>: bill <? print $sid?>">
						<input type="hidden" name="destination" value="<? print $company_name?>: bill <? print $sid?>">
						<input type="hidden" name="type" value="numb">
						<input type="hidden" name="to-account" value="<? print $yandex?>">
						<input type="hidden" name="receiver" value="<? print $yandex?>">
						<input type="hidden" name="sum" value="<? print $money_yandex?>">
						<input type="hidden" name="sum_k" value="<? print $money_yandex_k?>">
						<input type="hidden" name="FormComment" value="<? print $company_name?>: bill <? print $sid?>">
						<input type="hidden" name="js" value="0">
						<input type="hidden" name="isDirectPaymentFormSubmit" value="true">
						<input type="hidden" name="showcase_comm" value="0.5%">
						<input type="hidden" name="p2payment" value="1">
						<input type="hidden" name="suspendedPaymentsAllowed" value="true">
						<input type="hidden" name="secureparam5" value="5">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_yandex?> <? print $_lang[PaySokraschenieRubl]?>">
			                        </form>

					<? }
				}
				if ($payy->type == "superlend") {
					$money_wmz = $money_usd + ($money_usd/100)*$payy->small1; $money_wmz = round($money_wmz,2);

			                $wmz=$payy->text1;
			                $wmsecret=decodePwd($payy->pass1);
					if ($wmz and $wmsecret) { 
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_wmz',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form target=_blank method="POST" style="margin: 0;" action="http://superlend.ru/payment.php" accept-charset="windows-1251">
			                        <input type=hidden name=LMI_PAYMENT_DESC value="<? print $company_name?>: bill <? print $sid?>">
			                        <input type=hidden name=LMI_PAYEE_PURSE value="<? print $wmz?>">
			                        <input type=hidden name=LMI_PAYMENT_AMOUNT value="<? print $money_wmz?>">
						<input type=hidden name=BILL_ID value="<? print $r->id?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_wmz?> WMZ">
			                        </form>
					<? }
				}
				if ($payy->type == "perfectmoney") {
					$money_perfect = $money_usd + ($money_usd/100)*$payy->small1; $money_perfect = round($money_perfect,2);

			                $perfect_id = $payy->text1;
			                $perfect_name = $payy->text2;
			                $perfect_pass = decodePwd($payy->pass1);
					if ($perfect_id and $perfect_pass) { 
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_perfect',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); 

						if ($_SESSION["userLang"] == "russian") { $userLng = "ru_RU"; }
						else if ($_SESSION["userLang"] == "ukrainian") { $userLng = "uk_UA"; }
						else { $userLng = "en_US"; }

						?>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://perfectmoney.com/api/step1.asp">
			                        <input type=hidden name=PAYEE_ACCOUNT value="<? print $perfect_id?>">
			                        <input type=hidden name=PAYEE_NAME value="<? print $perfect_name?>">
			                        <input type=hidden name=PAYMENT_AMOUNT value="<? print $money_perfect?>">
			                        <input type=hidden name=PAYMENT_UNITS value="USD">
						<input type=hidden name=PAYMENT_ID value="<? print $r->id?>">
			                        <input type=hidden name=STATUS_URL value="<? print $full_www_path?>online_perfectmoney.php">
		                        	<input type=hidden name=PAYMENT_URL value="<? print $payment_url?>">
		                        	<input type=hidden name=PAYMENT_URL_METHOD value="POST">
		                	        <input type=hidden name=NOPAYMENT_URL value="<? print $nopayment_url?>">
		                        	<input type=hidden name=NOPAYMENT_URL_METHOD value="POST">
			                        <input type=hidden name=SUGGESTED_MEMO value="<? print $company_name?>: bill <? print $sid?>">
			                        <input type=hidden name=SUGGESTED_MEMO_NOCHANGE value="1">
			                        <input type=hidden name=INTERFACE_LANGUAGE value="<? print $userLng?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_perfect?> $">
			                        </form>
					<? }
				}
				if ($payy->type == "pro") {
					$money_pro = $money_rub + ($money_rub/100)*$payy->small1; $money_pro = round($money_pro,2);

			                $pro_client=$payy->text1;
			                $pro_ra=$payy->text2;
			                $pro_secret=decodePwd($payy->pass1);
					if ($pro_client and $pro_ra and $pro_secret) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_pro',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

						<form target=_blank action=http://merchant.prochange.ru/pay.pro method=POST accept-charset="windows-1251">
						<input type=hidden name=PRO_CLIENT value='<? print $pro_client?>'>
						<input type=hidden name=PRO_RA value='<? print $pro_ra?>'>
						<input type=hidden name=PRO_PAYMENT_DESC value='<? print $company_name?>: bill <? print $sid?>'>
						<input type=hidden name=PRO_SUMMA value='<? print $money_pro?>'>
						<input type=hidden name=PRO_FIELD_1 value='<? print $r->id?>'>
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_pro?> <? print $_lang[PaySokraschenieRubl]?>">
						</form>

					<? }
				}
				if ($payy->type == "egold") {
					$money_egold = $money_usd + ($money_usd/100)*$payy->small1; $money_egold = round($money_egold,2);

					$egold=$payy->text1;
					if ($egold) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_egold',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form target=_blank action="https://www.e-gold.com/sci_asp/payments.asp" method="POST">
	       			                <input class=input type="hidden" name="PAYEE_ACCOUNT" value="<? print $egold?>">
			                        <input class=input type="hidden" name="PAYEE_NAME" value="<? print $company_name?>">
		                	        <input class=input type="hidden" name="PAYMENT_AMOUNT" value="<? print $money_egold?>">
		        	                <input class=input type="hidden" name="PAYMENT_UNITS" value="1">
			                        <input class=input type="hidden" name="PAYMENT_METAL_ID" value="1">
			                        <input class=input type="hidden" name="PAYMENT_ID" value="<? print $r->id?>">
			                        <input class=input type="hidden" name="STATUS_URL" value="<? print $full_www_path?>online_egold.php">
		                        	<input class=input type="hidden" name="PAYMENT_URL" value="<? print $payment_url?>">
		                	        <input class=input type="hidden" name="NOPAYMENT_URL" value="<? print $nopayment_url?>">
		        	                <input class=input type="hidden" name="SUGGESTED_MEMO" value="<? print $company_name?>: bill <? print $sid?>">
			                        <input class=input type="hidden" name="BAGGAGE_FIELDS" value="">
			                        <input class=button type="submit" name="PAYMENT_METHOD" value="<? print $_lang[Pay]?> <? print $money_egold?> $">
			                        </form>

					<? }
				}
				if ($payy->type == "rupay") {
					$money_rupay = $money_rub + ($money_rub/100)*$payy->small1; $money_rupay = round($money_rupay,2);

					$rupay=$payy->text2;
					$rupaysecret=decodePwd($payy->small1);
					if ($rupay and $rupaysecret) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_rupay',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

						<form target=_blank action="https://rbkmoney.ru/acceptpurchase.aspx" accept-charset="windows-1251">
						<input class=input type="hidden" name="eshopId" value="<? print $rupay?>">
						<input class=input type="hidden" name="orderId" value="<? print $r->id?>">
						<input class=input type="hidden" name="serviceName" value="<? print $company_name?>: bill <? print $sid?>">
						<input class=input type="hidden" name="recipientAmount" value="<? print $money_rupay?>">
						<input class=input type="hidden" name="recipientCurrency" value="RUR">
			                        <input class=input type="hidden" name="successUrl" value="<? print $payment_url?>">
						<input class=input type="hidden" name="failUrl" value="<? print $nopayment_url?>">
						<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_rupay?> <? print $_lang[PaySokraschenieRubl]?>">
						</form>
		                        <? }
				}
				if ($payy->type == "zpay") {
					$money_zpay = $money_rub + ($money_rub/100)*$payy->small1; $money_zpay = round($money_zpay,2);

					$zpayid=$payy->text2;
					$zpaysecret=decodePwd($payy->pass1);
					if ($zpaysecret and $zpayid) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_zpay',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://z-payment.com/merchant.php" accept-charset="windows-1251">
			                        <input type=hidden name=LMI_PAYMENT_DESC value="<? print $company_name?>: bill <? print $sid?>">
	        		                <input type=hidden name=LMI_PAYEE_PURSE value="<? print $zpayid?>">
						<input type=hidden name=LMI_PAYMENT_NO value="<? print $r->id?>">
						<input type=hidden name=LMI_PREREQUEST value="0">
	        		                <input type=hidden name=LMI_PAYMENT_AMOUNT value="<? print $money_zpay?>">
						<input type=hidden name=LMI_SUCCESS_URL value="<? print $payment_url?>">
						<input type=hidden name=LMI_FAIL_URL value="<? print $nopayment_url?>">
						<input type=hidden name=CLIENT_MAIL value="<? print $user->email?>">
						<input type=hidden name=BILL_ID value="<? print $r->id?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_zpay?> ZP">
			                        </form>
					<? }
				}
				if ($payy->type == "robox") {
					$robox_currency=$payy->currency;
					if ($robox_currency) {
						$robox_currency = GetCurrencyByCode($robox_currency);
						$money_robox = $money*$robox_currency->koeficient;
						$robox_symbol = $robox_currency->symbol;
					} else {
						$money_robox = $money_usd;
						$robox_symbol = "\$";
					}
					$money_robox = $money_robox + ($money_robox/100)*$payy->small1; $money_robox = round($money_robox,2);

					$robox=$payy->text1;
					$robox_pass1=decodePwd($payy->pass1);
					if ($robox and $robox_pass1) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_robox',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

						<form target=_blank method="POST" style="margin: 0;" action="http://merchant.roboxchange.com/Index.aspx" accept-charset="windows-1251">
						<input type=hidden name=MrchLogin value=<? print $robox?>>
						<input type=hidden name=OutSum value=<? print $money_robox?>>
						<input type=hidden name=InvId value=<? print $r->id?>>
						<input type=hidden name=Desc value="<? print $company_name?>: bill <? print $sid?>">
						<input type=hidden name=SignatureValue value="<? print md5("$robox:$money_robox:$r->id:$robox_pass1")?>">
						<input type=hidden name=IncCurrLabel value=WMZ>
						<input type=hidden name=Culture value=ru>
						<input class=button type=submit value='<? print $_lang[Pay]?> <? print $money_robox?> <? print $robox_symbol?>'>
						</form>
					<? }
				}

				if ($payy->type == "ikass") {
					$money_ikass = $money_usd + ($money_usd/100)*$payy->small1; $money_ikass = round($money_ikass,2);

					$ikass_id=$payy->text1;
					$ikass_pass=decodePwd($payy->pass1);
					if ($ikass_id and $ikass_pass) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_ikass',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form target=_blank method="POST" style="margin: 0;" action="http://www.interkassa.com/lib/payment.php">
	        		                <input type=hidden name=ik_shop_id value="<? print $ikass_id?>">
	        		                <input type=hidden name=ik_payment_amount value="<? print $money_ikass?>">
						<input type=hidden name=ik_payment_id value="<? print $r->id?>">
			                        <input type=hidden name=ik_payment_desc value="bill <? print $sid?>">
						<input type=hidden name=ik_paysystem_alias value="<? print $payid?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_ikass?> $">
			                        </form>
					<? }
				}
				if ($payy->type == "easypay") {
					$money_easypay = $money_byr + ($money_byr/100)*$payy->small1; $money_easypay = ceil($money_easypay);

					$easypay_merno=$payy->text1;
					$easypay_pass=decodePwd($payy->pass1);

					if ($easypay_merno and $easypay_pass) {
						if (!$r->merchantId) {
							if (strlen($purse) == 8) {
								$easypay = new EASYPAY;
								$easypay->init($easypay_merno,$easypay_pass);

								$result=$easypay->createBill($r->id, $money_easypay, 30, $purse, "$company_name: bill $sid", "$company_name: bill $sid", "");
		
								if ($result) {
									@mysql_query("update bills set paymentSystemId='$payy->id',merchantId='$r->id',merchantmoney='$money_easypay',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
									print $_lang[PayBillCreateEasyPaySuccess]." ($purse).<BR><BR>";

								} else {
									print "<font color=red>".$_lang[Error].": $easypay->error</font><BR><BR>";
								}
							}
							else { 
								print "<font color=red>".$_lang[Error].": ".$_lang[PayErrorPurseNeed].".</font><BR><BR>"; $easypay->error = "1";
								?>
					                        <form method="POST" style="margin: 0;" action="billing.php">
			        	        	        <input type=hidden name=do value="<? print $do?>">
			        		                <input type=hidden name=sub value="gotomerchant">
				                	        <input type=hidden name=id value="<? print $id?>">
				                        	<input type=hidden name=paytype value="easypay">
								<input type=hidden name=paymentId value=<? print $payy->id?>>
								<? print $_lang[PayInputEasyPayPurse]?>:<BR>
								<input class=input type=text name=purse size=10> <input class=button type=submit value="<? print $_lang[PayCreateBillForSumm]?> <? print $money_easypay?> <? print $_lang[PaySokraschenieRubl]?>">
				        	                </form>
								<?
							}
						} else if (!$check) {
							print $_lang[PayErrorBillCreateEasyPayAlready].".<BR><BR>";
						}

						$billPayed = 0;
						$billDeleted = 0;
						if ($check and $r->merchantId) {
							$easypay = new EASYPAY;
							$easypay->init($easypay_merno,$easypay_pass);

							$result=$easypay->checkBill($r->id);

							if ($result == "1") {
								$billPayed = 1;
								MakeBillPayed($r->id,1,"EasyPay Merchant");
								print $_lang[PayBillStatus].": ".$_lang[BillPayed].".<BR><BR>";
							} else if ($result == "-1") {
								$billDeleted = 1;
								@mysql_query("update bills set merchantmoney='',merchantId='',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
								print $_lang[PayBillStatus].": ".$_lang[BillDeleted].".<BR><BR>";
							} else {
								print $_lang[PayBillStatus].": ".$_lang[BillNotPayed].".<BR><BR>";
							}
						}

						if (!$easypay->error and !$billPayed and !$billDeleted) {
							print $_lang[PayGotoEasyPay];

							?>
				                        <form target=_blank method="POST" style="margin: 0;" action="billing.php">
			                	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
				                        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="easypay">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
				                        <input type=hidden name=check value="1">
							<input class=button type=submit value="<? print $_lang[PayCheckBillStatus]?>">
				                        </form>
							<?

						}
					}

				}
				if ($payy->type == "webpay") {
					$money_webpay = $money_byr + ($money_byr/100)*$payy->small1; $money_webpay = ceil($money_webpay);

			                $webpay_storeid=$payy->text1;
			                $webpay_secret=decodePwd($payy->pass1);
			                if ($webpay_storeid and $webpay_secret) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_webpay',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

						if ($WEBPAY_TEST) { $WEBPAY_TEST = 1; } else { $WEBPAY_TEST = 0; }

						$seed = time();
						$sign = sha1($seed.$webpay_storeid.$r->id.$WEBPAY_TEST."BYR".$money_webpay.$webpay_secret);
						?>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://secure.sandbox.webpay.by:8843/">
	        		                <input type=hidden name="*scart">
	        		                <input type=hidden name=wsb_storeid value="<? print $webpay_storeid?>">
	        		                <input type=hidden name=wsb_store value="<? print $company_name?>">
	        		                <input type=hidden name=wsb_order_num value="<? print $r->id?>">
	        		                <input type=hidden name=wsb_currency_id value="BYR">
	        		                <input type=hidden name=wsb_version value="2">
	        		                <input type=hidden name=wsb_language_id value="russian">
	        		                <input type=hidden name=wsb_seed value="<? print $seed?>">
	        		                <input type=hidden name=wsb_signature value="<? print $sign?>">
	        		                <input type=hidden name=wsb_return_url value="<? print GetSetting("payment_url")?>">
	        		                <input type=hidden name=wsb_cancel_return_url value="<? print GetSetting("nopayment_url")?>">
	        		                <input type=hidden name=wsb_notify_url value="<? print $full_www_path?>online_webpay.php">
	        		                <input type=hidden name=wsb_test value="<? print $WEBPAY_TEST?>">
	        		                <input type=hidden name=wsb_invoice_item_name[0] value="<? print "Услуги по счету $sid"?>">
	        		                <input type=hidden name=wsb_invoice_item_quantity[0] value="1">
	        		                <input type=hidden name=wsb_invoice_item_price[0] value="<? print $money_webpay?>">
	        		                <input type=hidden name=wsb_total value="<? print $money_webpay?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_webpay?> <? print $_lang[PaySokraschenieRubl]?>">
			                        </form>
					<? }
				}
				if ($payy->type == "portmone") {
					$money_portmone = $money_uah + ($money_uah/100)*$payy->small1; $money_portmone = round($money_portmone,2);

			                $portmone_id=$payy->text1;
	        		        $portmone_login=$payy->text2;
			                $portmone_password=decodePwd($payy->pass1);
					if ($portmone_id and $portmone_login and $portmone_password) {
						if (!$check) {
							@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_portmone',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); 

							?>
				                        <form target=_blank method="POST" style="margin: 0;" action="https://www.portmone.com.ua/secure/gate/pay.php" accept-charset="windows-1251">
	        			                <input type=hidden name=PAYEE_ID value="<? print $portmone_id?>">
							<input type=hidden name=SHOPORDERNUMBER value="<? print $r->id?>">
	        			                <input type=hidden name=BILL_AMOUNT value="<? print $money_portmone?>">
			                	        <input type=hidden name=DESCRIPTION value="<? print $company_name?>: bill <? print $sid?>">
							<input type=hidden name=OUT_URL value="<? print $full_www_path?>billing.php?do=<? print $do?>&sub=gotomerchant&id=<? print $id?>&paytype=portmone&check=1">
				                        <input type=hidden name=LANG value="<? print $_lang[LangCode]?>">
				                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_portmone?> <? print $_lang[PaySokraschenieGrivna]?>">
				                        </form><BR><BR>
							<? 
						} else {
							$billPayed = 0;

							$portmone = new PORTMONE;
							$portmone->init($portmone_id,$portmone_login,$portmone_password);

							$result=$portmone->checkBill($r->id);

							if ($result) {
								if ($result == $r->merchantmoney) {
									$billPayed = 1;
									MakeBillPayed($r->id,1,"Portmone Merchant");
									print $_lang[PayBillStatus].": ".$_lang[BillPayed].".<BR><BR>";
								} else {
									print "<font color=red>".$_lang[Error].": ".$_lang[PayErrorBadSumm]."</font><BR><BR>";
								}
							} else {
								if ($portmone->error) { $portmone->error = " (".$portmone->error.")"; }
								print $_lang[PayBillStatus].": ".$_lang[BillNotPayed].$portmone->error."<BR><BR>";
							}
						}

						if (!$billPayed and $r->merchantmoney > 0) {
							print $_lang[PayPortmoneCheckBill];

							?>
				                        <form method="POST" style="margin: 0;" action="billing.php">
			                	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
				                        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="portmone">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
				                        <input type=hidden name=check value="1">
							<input class=button type=submit value="<? print $_lang[PayCheckBillStatus]?>">
				                        </form>
							<?

						}

					}
				}
				if ($payy->type == "privatbank") {
					$money_privatbank = $money_uah + ($money_uah/100)*$payy->small1; $money_privatbank = round($money_privatbank,2);
					$money_privatbank_usd = $money_usd + ($money_usd/100)*$payy->small1; $money_privatbank_usd = round($money_privatbank_usd,2);

					$privatbank_id=$payy->text1;
					$privatbank_pass=decodePwd($payy->pass1);
					$privatbank_name=$payy->text2;
					if ($privatbank_id and $privatbank_pass and $privatbank_name) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_privatbank',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

						$desc = "Оплата счета №".$sid;

						$money_privatbank = sprintf("%01.2f", $money_privatbank); $amount = preg_replace("/\./ui", "", $money_privatbank); while( strlen($amount)<12) {$amount="0".$amount;}
						$money_privatbank_usd = sprintf("%01.2f", $money_privatbank_usd); $amount_usd = preg_replace("/\./ui", "", $money_privatbank_usd); while( strlen($amount_usd)<12) {$amount_usd="0".$amount_usd;}
						if ($PRIVATBANK_TEST) { $orderID = "Test_".$privatbank_name."_".time()."_$r->id"; } else { $orderID = time()."_".$r->id; }

						$hash = sha1($privatbank_pass.$privatbank_id."414963".$orderID.$amount."980".$amount_usd."840".$desc);
						$hash = hexbin($hash);
						$hash = base64_encode($hash);

#						$path = preg_replace("/http:/ui","https:",$full_www_path);
						?>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://ecommerce.liqpay.com/ecommerce/CheckOutPagen">
						<input type='hidden' value='1.0.0' name='Version'>
						<input type='hidden' value='414963' name='acqid'>
						<input type='hidden' value='<? print $privatbank_id?>' name='merid'>
						<input type='hidden' value='<? print $orderID?>' name='orderid'>
						<input type='hidden' value='<? print $full_www_path?>billing.php' name='merrespurl'>
						<input type='hidden' value='<? print $full_www_path?>online_privatbank.php' name='merrespurl2'>
						<input type='hidden' value='<? print $amount?>' name='purchaseamt'>
						<input type='hidden' value='980' name='purchasecurrency'>
						<input type='hidden' value='<? print $amount_usd?>' name='purchaseamt2'>
						<input type='hidden' value='840' name='purchasecurrency2'>
						<input type='hidden' value='2' name='purchasecurrencyexponent'>
						<input type='hidden' value ='<? print $hash?>' name='signature'>
						<input type='hidden' value='<? print $desc?>' name='orderdescription'>

			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_privatbank?> <? print $_lang[PaySokraschenieGrivna]?>">
			                        </form>
					<? }
				}
				if ($payy->type == "payu") {
					$money_payu = $money_uah + ($money_uah/100)*$payy->small1; $money_payu = round($money_payu,2);

					$payu_id=$payy->text1;
					$payu_secret=decodePwd($payy->pass1);
					$payu_vat=$payy->select1;
					$payu_nds=$payy->select2;
					$payu_test=$payy->check1;
					if ($payu_id and $payu_secret) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_payu',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

						$arr[MERCHANT] = $payu_id;
						$arr[ORDER_REF] = $r->id;
						$arr[ORDER_DATE] = date("Y-m-d H:i:s");
						$arr[ORDER_PNAME] = "Оплата счета №".$sid;
						$arr[ORDER_PCODE] = $r->id;
						$arr[ORDER_PRICE] = $money_payu;
						$arr[ORDER_QTY] = 1;
						$arr[ORDER_VAT] = $payu_vat;
						$arr[ORDER_SHIPPING] = 0;
						$arr[PRICES_CURRENCY] = "UAH";
						$arr[ORDER_PRICE_TYPE] = $payu_nds;

						$str = "";
						while (list($k,$v) = each($arr)) {
							$str .= mb_strlen($v, '8bit').$v;
						}

						$hash = hash_hmac("md5", $str, $payu_secret);

						$arr[ORDER_HASH] = $hash;
						if ($payu_test) { $arr[TESTORDER] = "TRUE"; $arr[DEBUG] = "1"; } else { $arr[TESTORDER] = "FALSE"; $arr[DEBUG] = "0"; }
						if ($_SESSION["userLang"] == "russian") { $arr[LANGUAGE] = "RU"; } else if ($_SESSION["userLang"] == "ukrainian") { $arr[LANGUAGE] = "UA"; } else { $arr[LANGUAGE] = "EN"; }
						$arr[BACK_REF] = $full_www_path."billing.php";

						?>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://secure.payu.ua/order/lu.php">
						<input type='hidden' value='<?=$arr[MERCHANT]?>' name='MERCHANT'>
						<input type='hidden' value='<?=$arr[ORDER_REF]?>' name='ORDER_REF'>
						<input type='hidden' value='<?=$arr[ORDER_DATE]?>' name='ORDER_DATE'>
						<input type='hidden' value='<?=$arr[ORDER_PNAME]?>' name='ORDER_PNAME[]'>
						<input type='hidden' value='<?=$arr[ORDER_PCODE]?>' name='ORDER_PCODE[]'>
						<input type='hidden' value='<?=$arr[ORDER_PRICE]?>' name='ORDER_PRICE[]'>
						<input type='hidden' value='<?=$arr[ORDER_QTY]?>' name='ORDER_QTY[]'>
						<input type='hidden' value='<?=$arr[ORDER_VAT]?>' name='ORDER_VAT[]'>
						<input type='hidden' value='<?=$arr[ORDER_SHIPPING]?>' name='ORDER_SHIPPING'>
						<input type='hidden' value='<?=$arr[PRICES_CURRENCY]?>' name='PRICES_CURRENCY'>
						<input type='hidden' value='<?=$arr[ORDER_PRICE_TYPE]?>' name='ORDER_PRICE_TYPE[]'>
						<input type='hidden' value='<?=$arr[ORDER_HASH]?>' name='ORDER_HASH'>
						<input type='hidden' value='<?=$arr[TESTORDER]?>' name='TESTORDER'>
						<input type='hidden' value='<?=$arr[DEBUG]?>' name='DEBUG'>
						<input type='hidden' value='<?=$arr[LANGUAGE]?>' name='LANGUAGE'>
						<input type='hidden' value='<?=$arr[BACK_REF]?>' name='BACK_REF'>
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_payu?> <? print $_lang[PaySokraschenieGrivna]?>">
			                        </form>
					<? }
				}
				if ($payy->type == "p24") {
					$p24_currency=$payy->currency;
					if (!$p24_currency) {$p24_currency="UAH";}

					$p24_currency = GetCurrencyByCode($p24_currency);
					$money_p24 = $money*$p24_currency->koeficient;
					$p24_symbol = $p24_currency->symbol;
					$p24_code = $p24_currency->code;

					$money_p24 = $money_p24 + ($money_p24/100)*$payy->small1; $money_p24 = round($money_p24,2);

					$p24_id=$payy->text1;
					$p24_pass=decodePwd($payy->pass1);
					if ($p24_id and $p24_pass) {
						$desc = $company_name.": bill ".$sid;

						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_p24',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

						<form target=_blank method="POST" style="margin: 0;" action="https://api.privatbank.ua/p24api/ishop" accept-charset="windows-1251">
						<input type="hidden" name="amt" value="<? print $money_p24?>">
						<input type="hidden" name="ccy" value="<? print $p24_code?>">
						<input type="hidden" name="merchant" value="<? print $p24_id?>">
						<input type="hidden" name="order" value="<? print $r->id."_".time()?>">
						<input type="hidden" name="details" value="<? print $desc?>">
						<input type="hidden" name="ext_details" value="<? print $r->id?>">
						<input type="hidden" name="pay_way" value="privat24">
						<input type="hidden" name="return_url" value="<? print GetSetting("payment_url")?>">
						<input type="hidden" name="server_url" value="<? print $full_www_path?>online_p24.php">
						<input class=button type=submit value='<? print $_lang[Pay]?> <? print $money_p24?> <? print $p24_symbol?>'>
						</form>
					<? }
				}
				if ($payy->type == "moneyua") {
					$currency = CURC;
					$currency = GetCurrencyByCode($currency);
					$money_submit = $money*$currency->koeficient;
					$submit_symbol = $currency->symbol;

					$money_moneyua = $money_uah + ($money_uah/100)*$payy->small1; $money_moneyua = round($money_moneyua,2);
					$money_submit = $money_submit + ($money_submit/100)*$payy->small1; $money_submit = round($money_submit,2);

					$moneyua_id=$payy->text1;
					$moneyua_secret=decodePwd($payy->pass1);
					$moneyua_desc=$payy->text2;
					if ($moneyua_id and $moneyua_secret) {
						if (!$moneyua_desc) { $moneyua_desc = "Оплата счета №{schet}"; }

						$profile = GetUserProfileByUserId($_SESSION["userId"]);
						if ($user->surname and $user->name) { $name = $user->surname." ".$user->name." ".$user->otchestvo; }
						else if ($profile->surname and $profile->name) { $name = $profile->surname." ".$profile->name." ".$profile->otchestvo; } 
						else { $name = $_SESSION["userLogin"]; }

						$moneyua_desc = preg_replace("/{schet}/ui","$sid",$moneyua_desc);
						$moneyua_desc = preg_replace("/{user}/ui","$name",$moneyua_desc);
						$moneyua_desc = preg_replace("/{login}/ui",$_SESSION["userLogin"],$moneyua_desc);

						$MERCHANT_INFO = $moneyua_id;
						$PAYMENT_TYPE = $payid;
						$PAYMENT_RULE = "";
						$PAYMENT_AMOUNT = $money_moneyua*100;
						$PAYMENT_ADDVALUE = "";
						$PAYMENT_INFO = $moneyua_desc;
						$PAYMENT_DELIVER = "";
						$PAYMENT_ORDER = $r->id."_".time();
						$PAYMENT_VISA = "";
						$PAYMENT_TESTMODE = "0";
						$PAYMENT_RETURNRES = $full_www_path."online_moneyua.php";
						$PAYMENT_RETURN = GetSetting("payment_url");
						$PAYMENT_RETURNMET = "2";

						$hash = "$MERCHANT_INFO:$PAYMENT_TYPE:$PAYMENT_RULE:$PAYMENT_AMOUNT:$PAYMENT_ADDVALUE:$PAYMENT_INFO:$PAYMENT_DELIVER:$PAYMENT_ORDER:$PAYMENT_VISA:$PAYMENT_TESTMODE:$PAYMENT_RETURNRES:$PAYMENT_RETURN:$PAYMENT_RETURNMET:$moneyua_secret";
						$hash = iconv("utf-8", "windows-1251", $hash);
						$hash = md5($hash);

						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_moneyua',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

						<form target=_blank method="POST" style="margin: 0;" action="http://money.ua/sale.php" accept-charset="windows-1251">
						<input type="hidden" name="PAYMENT_AMOUNT" value="<? print $PAYMENT_AMOUNT?>">
						<input type="hidden" name="PAYMENT_INFO" value="<? print $PAYMENT_INFO?>">
						<input type="hidden" name="PAYMENT_DELIVER" value="<? print $PAYMENT_DELIVER?>">
						<input type="hidden" name="PAYMENT_ADDVALUE" value="<? print $PAYMENT_ADDVALUE?>">
						<input type="hidden" name="MERCHANT_INFO" value="<? print $MERCHANT_INFO?>">
						<input type="hidden" name="PAYMENT_ORDER" value="<? print $PAYMENT_ORDER?>">
						<input type="hidden" name="PAYMENT_TYPE" value="<? print $PAYMENT_TYPE?>">
						<input type="hidden" name="PAYMENT_RULE" value="<? print $PAYMENT_RULE?>">
						<input type="hidden" name="PAYMENT_VISA" value="<? print $PAYMENT_VISA?>">
						<input type="hidden" name="PAYMENT_RETURNRES" value="<? print $PAYMENT_RETURNRES?>">
						<input type="hidden" name="PAYMENT_RETURN" value="<? print $PAYMENT_RETURN?>">
						<input type="hidden" name="PAYMENT_RETURNMET" value="<? print $PAYMENT_RETURNMET?>">
						<input type="hidden" name="PAYMENT_RETURNFAIL" value="<? print $PAYMENT_RETURN?>">
						<input type="hidden" name="PAYMENT_TESTMODE" value="<? print $PAYMENT_TESTMODE?>">
						<input type="hidden" name="PAYMENT_HASH" value="<? print $hash?>">

						<input class=button type=submit value='<? print $_lang[Pay]?> <? print $money_submit?> <? print $submit_symbol?>'>
						</form>
					<? }
				}
				if ($payy->type == "liqpay") {
					$liqpay_currency=$payy->currency;
					if ($liqpay_currency) {
						$liqpay_currency = GetCurrencyByCode($liqpay_currency);
						$money_liqpay = $money*$liqpay_currency->koeficient;
						$liqpay_symbol = $liqpay_currency->symbol;
						$liqpay_code = $liqpay_currency->code;
						if ($liqpay_code == "RUB") {$liqpay_code = "RUR";}
					} else {
						$money_liqpay = $money_usd;
						$liqpay_symbol = "\$";
						$liqpay_code = "USD";
					}
					$money_liqpay_card = $money_liqpay + ($money_liqpay/100)*$payy->small1; $money_liqpay_card = round($money_liqpay_card,2);
					$money_liqpay_phone = $money_liqpay + ($money_liqpay/100)*$payy->small2; $money_liqpay_phone = round($money_liqpay_phone,2);
					$money_liqpay_nalichnie = $money_liqpay + ($money_liqpay/100)*$payy->small3; $money_liqpay_nalichnie = round($money_liqpay_nalichnie,2);

					if ($payway == "card") {
						$money_liqpay = $money_liqpay_card;
					}
					else if ($payway == "liqpay") {
						$money_liqpay = $money_liqpay_phone;
					}
					else if ($payway == "delayed") {
						$money_liqpay = $money_liqpay_nalichnie;
					}

					$liqpay_id=$payy->text1;
					$liqpay_password=decodePwd($payy->pass1);
					if ($liqpay_id and $liqpay_password) {
						$desc = $company_name.": bill ".$sid;

						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_liqpay',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

						$xml="<request>      
							<version>1.2</version>
							<result_url>".GetSetting("payment_url")."</result_url>
							<server_url>".$full_www_path."online_liqpay.php</server_url>
							<merchant_id>$liqpay_id</merchant_id>
							<order_id>".$r->id."</order_id>
							<amount>".$money_liqpay."</amount>
							<currency>".$liqpay_code."</currency>
							<description>".$desc."</description>
							<default_phone></default_phone>
							<pay_way>".$payway."</pay_way> 
							</request>
							";
	
						$xml_encoded = base64_encode($xml); 
						$lqsignature = base64_encode(sha1($liqpay_password.$xml.$liqpay_password,1));

						?>
						<form target=_blank method="POST" style="margin: 0;" action="https://liqpay.com/?do=clickNbuy">
						<input type="hidden" name="operation_xml" value="<? print $xml_encoded?>">
						<input type="hidden" name="signature" value="<? print $lqsignature?>">
						<input class=button type=submit value='<? print $_lang[Pay]?> <? print $money_liqpay?> <? print $liqpay_symbol?>'>
						</form>
						<? 
					}
				}
				if ($payy->type == "twopay") {
					$twopay_currency=$payy->currency;
					if ($twopay_currency) {
						$twopay_currency = GetCurrencyByCode($twopay_currency);
						$money_twopay = $money*$twopay_currency->koeficient;
						$twopay_symbol = $twopay_currency->symbol;
						$twopay_code = $twopay_currency->code;
					} else {
						$money_twopay = $money_usd;
						$twopay_symbol = "\$";
						$twopay_code = "USD";
					}
					$money_twopay = $money_twopay + ($money_twopay/100)*$payy->small1; $money_twopay = round($money_twopay,2);

					$twopay_id=$payy->text1;
					$twopay_secret=decodePwd($payy->pass1);
					if ($twopay_id and $twopay_secret) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_twopay',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

						<form target=_blank method="POST" style="margin: 0;" action="https://2pay.ru/oplata/">
						<input type="hidden" name="id" value="<? print $twopay_id?>">
						<input type="hidden" name="v1" value="<? print $r->id?>">
						<input type="hidden" name="amount" value="<? print $money_twopay?>">
						<input class=button type=submit value='<? print $_lang[Pay]?> <? print $money_twopay?> <? print $twopay_symbol?>'>
						</form>
					<? }
				}
				if ($payy->type == "smscoin") {
					$money_smscoin = $money_usd + ($money_usd/100)*$payy->small1; $money_smscoin = round($money_smscoin,2);

					$smscoin_id=$payy->text1;
					$smscoin_s_clear_amount=$payy->select1;
					$smscoin_secret=decodePwd($payy->pass1);
					if ($smscoin_id and $smscoin_secret) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_smscoin',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

						print $_lang[PayGotoSMSCoin];

						if ($smscoin_s_clear_amount) { $smscoin_s_clear_amount = 1; } else { $smscoin_s_clear_amount = 0; }

						$desc = "bill # $sid";

						$sign = md5($smscoin_id."::".$r->id."::".$money_smscoin."::".$smscoin_s_clear_amount."::".$desc."::".$smscoin_secret);
						?>

			                        <form target=_blank method="POST" style="margin: 0;" action="http://bank.smscoin.com/language/<? print $_SESSION["userLang"]?>/bank/">
	        		                <input type=hidden name=s_purse value="<? print $smscoin_id?>">
						<input type=hidden name=s_order_id value="<? print $r->id?>">
	        		                <input type=hidden name=s_amount value="<? print $money_smscoin?>">
	        		                <input type=hidden name=s_clear_amount value="<? print $smscoin_s_clear_amount?>">
			                        <input type=hidden name=s_description value="<? print $desc?>">
	        		                <input type=hidden name=s_sign value="<? print $sign?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_smscoin?> $">
			                        </form>
					<? }
				}
				if ($payy->type == "paypal") {
					$paypal_currency=$payy->currency;
					if ($paypal_currency) {
						$paypal_currency = GetCurrencyByCode($paypal_currency);
						$money_paypal = $money*$paypal_currency->koeficient;
						$paypal_symbol = $paypal_currency->symbol;
						$paypal_code = $paypal_currency->code;
					} else {
						$money_paypal = $money_usd;
						$paypal_symbol = "\$";
						$paypal_code = "USD";
					}
					$money_paypal = $money_paypal + ($money_paypal/100)*$payy->small1; $money_paypal = round($money_paypal,2);

					$paypal=$payy->text1;
					if ($paypal) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_paypal',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://www.paypal.com/cgi-bin/webscr">
	        		                <input type=hidden name=business value="<? print $paypal?>">
	        		                <input type=hidden name=currency_code value="<? print $paypal_code?>">
	        		                <input type=hidden name=return value="<? print GetSetting("payment_url")?>">
	        		                <input type=hidden name=cancel_return value="<? print GetSetting("nopayment_url")?>">
	        		                <input type=hidden name=notify_url value="<? print $full_www_path."online_paypal.php"?>">
	        		                <input type=hidden name=cmd value="_xclick">
			                        <input type=hidden name=item_name value="<? print $company_name?>: bill <? print $sid?>">
	        		                <input type=hidden name=amount value="<? print $money_paypal?>">
						<input type=hidden name=item_number value="<? print $r->id?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_paypal?> <? print $paypal_symbol?>">
			                        </form>
					<? }
				}
				if ($payy->type == "twoco") {
					$money_twoco = $money_usd + ($money_usd/100)*$payy->small1; $money_twoco = round($money_twoco,2);

					$twoco_id=$payy->text1;
					$twoco_secret=decodePwd($payy->pass1);
					if ($twoco_id and $twoco_secret) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_twoco',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://www.2checkout.com/2co/buyer/purchase">
	        		                <input type=hidden name=sid value="<? print $twoco_id?>">
						<input type=hidden name=cart_order_id value="<? print $r->id?>">
	        		                <input type=hidden name=total value="<? print $money_twoco?>">
	        		                <input type=hidden name=x_receipt_link_url value="<? print $full_www_path."online_twoco.php"?>">
	        		                <input type=hidden name=tco_currency value="USD">
	        		                <input type=hidden name=fixed value="Y">

			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_twoco?> $">
			                        </form>
					<? }
				}
				if ($payy->type == "authorize") {
					$money_authorize = $money_usd + ($money_usd/100)*$payy->small1; $money_authorize = round($money_authorize,2);

					$authorize_login=$payy->text1;
					$authorize_secret=decodePwd($payy->pass1);
					if ($authorize_login and $authorize_secret) {
						$x_time = time();
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_authorize',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://secure.authorize.net/gateway/transact.dll">
	        		                <input type=hidden name=x_version value="3.1">
	        		                <input type=hidden name=x_show_form value="PAYMENT_FORM">
	        		                <input type=hidden name=x_relay_response value="TRUE">
	        		                <input type=hidden name=x_login value="<? print $authorize_login?>">
						<input type=hidden name=x_fp_sequence value="<? print $r->id?>">
						<input type=hidden name=x_fp_timestamp value="<? print $x_time?>">
						<input type=hidden name=x_fp_hash value="<? print hmac($authorize_secret,$authorize_login."^".$r->id."^".$x_time."^".sprintf("%01.2f", $money_authorize)."^")?>">
	        		                <input type=hidden name=x_receipt_link_url value="<? print GetSetting("payment_url")?>">
	        		                <input type=hidden name=x_relay_url value="<? print $full_www_path."online_authorize.php"?>">
			                        <input type=hidden name=x_description value="<? print $company_name?>: bill <? print $sid?>">
	        		                <input type=hidden name=x_amount value="<? print sprintf("%01.2f", $money_authorize)?>">
						<input type=hidden name=x_invoice_num value="<? print $r->id?>">
	        		                <input type=hidden name=x_cust_id value="<? print $user->login?>">

			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_authorize?> $">
			                        </form>
					<? }
				}
				if ($payy->type == "upc") {
					$upc_currency=$payy->currency;
					if ($upc_currency) {
						$upc_currency = GetCurrencyByCode($upc_currency);
						$money_upc = $money*$upc_currency->koeficient;
						$upc_symbol = $upc_currency->symbol;
						$upc_code = $upc_currency->code;
					} else {
						$upc_currency = GetCurrencyByCode("UAH");
						$money_upc = $money*$upc_currency->koeficient;
						$upc_symbol = $upc_currency->symbol;
						$upc_code = $upc_currency->code;
					}
					$money_upc = $money_upc + ($money_upc/100)*$payy->small1; $money_upc = round($money_upc,2);

					$upc_mid=$payy->text1;
					$upc_tid=$payy->text2;
					$upc_pem=$payy->text3;
					$upc_crt=$payy->text4;
					if ($upc_mid and $upc_tid and $upc_pem and $upc_crt and file_exists($upc_pem) and file_exists($upc_crt)) {
						$amount = sprintf("%01.2f", $money_upc); $amount = preg_replace("/\./ui", "", $amount);

						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$amount',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); 

						if ($upc_code == "USD") { $upc_code = "840"; }
						else if ($upc_code == "EUR") { $upc_code = "978"; }
						else if ($upc_code == "RUB") { $upc_code = "643"; }
						else if ($upc_code == "UAH") { $upc_code = "980"; }

						if ($_SESSION["userLang"] == "russian") { $userLng = "ru"; }
						else if ($_SESSION["userLang"] == "ukrainian") { $userLng = "uk"; }
						else { $userLng = "en"; }

						$PurchaseTime = date("ymdHis");

						$data = $upc_mid.";".$upc_tid.";".$PurchaseTime.";".$r->id.";".$upc_code.";".$amount.";;";
						$fp = fopen($upc_pem, "r");
						$priv_key = fread($fp, 8192);
						fclose($fp);
						$pkeyid = openssl_get_privatekey($priv_key);
						openssl_sign( $data , $signature, $pkeyid);
						openssl_free_key($pkeyid);
						$b64sign = base64_encode($signature);
						?>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://secure.upc.ua/ecgtest/enter">
						<INPUT TYPE="HIDDEN" NAME="Version" VALUE="1">
						<INPUT TYPE="HIDDEN" NAME="MerchantID" VALUE="<? print $upc_mid?>">
						<INPUT TYPE="HIDDEN" NAME="TerminalID" VALUE="<? print $upc_tid?>">
						<INPUT TYPE="HIDDEN" NAME="TotalAmount" VALUE="<? print $amount?>">
						<INPUT TYPE="HIDDEN" NAME="Currency" VALUE="<? print $upc_code?>">
						<INPUT TYPE="HIDDEN" NAME="locale" VALUE="<? print $userLng?>">
						<INPUT TYPE="HIDDEN" NAME="OrderID" VALUE="<? print $r->id?>">
						<INPUT TYPE="HIDDEN" NAME="PurchaseTime" VALUE="<? print $PurchaseTime?>">
						<INPUT TYPE="HIDDEN" NAME="PurchaseDesc" VALUE="bill <? print $sid?>">
						<INPUT TYPE="HIDDEN" NAME="Signature" VALUE="<? print $b64sign?>">

			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_upc?> <? print $upc_symbol?>">
			                        </form>
					<? }
				}
				if ($payy->type == "a1lite") {
					$money_a1lite = $money_rub + ($money_rub/100)*$payy->small1; $money_a1lite = round($money_a1lite,2);

			                $a1lite_key=$payy->text1;
			                $a1lite_secret=decodePwd($payy->pass1);
					if ($a1lite_key and $a1lite_secret) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_a1lite',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://partner.a1pay.ru/a1lite/input">
			                        <input type=hidden name=key value="<? print $a1lite_key?>">
			                        <input type=hidden name=cost value="<? print $money_a1lite?>">
			                        <input type=hidden name=name value="<? print $company_name?>: bill <? print $sid?>">
						<input type=hidden name=order_id value="<? print $r->id?><? print rand(11111,99999)?>">
						<input type=hidden name=default_email value="<? print $user->email?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_a1lite?> <? print $_lang[PaySokraschenieRubl]?>">
			                        </form>
					<? }
				}
				if ($payy->type == "telemoney") {
					$money_telemoney = $money_rub + ($money_rub/100)*$payy->small1; $money_telemoney = round($money_telemoney,2);

					$telemoney_id=$payy->text1;
					$telemoney_secret=decodePwd($payy->pass1);
					if ($telemoney_id and $telemoney_secret) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_telemoney',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

						<form target=_blank method="POST" style="margin: 0;" action="https://telemoney.ru/transfer" accept-charset="windows-1251">
						<input class=input type="hidden" name="TM_TARGET" value="<? print $telemoney_id?>">
						<input class=input type="hidden" name="TM_SUM" value="<? print $money_telemoney?>">
						<input class=input type="hidden" name="TM_COMMENT" value="<? print $company_name?>: bill <? print $sid?>">
						<input class=input type="hidden" name="TM_EXTRA" value="<? print $r->id?>">
						<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_telemoney?> <? print $_lang[PaySokraschenieRubl]?>">
						</form>
		                        <? }
				}
				if ($payy->type == "intellectmoney") {
					$money_intellectmoney = $money_rub + ($money_rub/100)*$payy->small1; $money_intellectmoney = round($money_intellectmoney,2);

					$intellectmoney_id=$payy->text1;
					$intellectmoney_secret=decodePwd($payy->pass1);
					if ($intellectmoney_id and $intellectmoney_secret) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_intellectmoney',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://Merchant.IntellectMoney.ru/" accept-charset="windows-1251">
			                        <input type=hidden name=LMI_PAYMENT_DESC value="<? print $company_name?>: bill <? print $sid?>">
			                        <input type=hidden name=LMI_PAYEE_PURSE value="<? print $intellectmoney_id?>">
			                        <input type=hidden name=LMI_PAYMENT_AMOUNT value="<? print $money_intellectmoney?>">
						<input type=hidden name=LMI_SUCCESS_URL value="<? print $payment_url?>">
						<input type=hidden name=LMI_FAIL_URL value="<? print $nopayment_url?>">
						<input type=hidden name=LMI_PAYMENT_NO value="<? print $r->id?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_intellectmoney?> <? print $_lang[PaySokraschenieRubl]?>">
			                        </form>
		                        <? }
				}
				if ($payy->type == "assist") {
					$money_assist = $money_rub + ($money_rub/100)*$payy->small1; $money_assist = round($money_assist,2);

					$assist_shopid=$payy->text1;
					$assist_test=$payy->check1;
					if ($assist_shopid) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_assist',merchantType='assist' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form target=_blank method="POST" style="margin: 0;" action="<? if ($assist_test) { print "https://test.assist.ru/shops/purchase.cfm"; } else { print "https://secure.assist.ru/shops/purchase.cfm"; } ?>" accept-charset="windows-1251">
			                        <input type=hidden name=Shop_IDP value="<? print $assist_shopid?>">
						<input type=hidden name=Order_IDP value="<? print $r->id?>">
			                        <input type=hidden name=Subtotal_P value="<? print $money_assist?>">
			                        <input type=hidden name=Comment value="<? print $company_name?>: bill <? print $sid?>">

						<? if ($assist_test) { if (!$ASSIST_RESULT) { $ASSIST_RESULT = "AS000"; } ?> <input type=hidden name=DemoResult value="<? print $ASSIST_RESULT?>"> <? } ?>
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_assist?> <? print $_lang[PaySokraschenieRubl]?>">
			                        </form>
		                        <? }
				}
				if ($payy->type == "kkbkz") {
					$money_kkbkz = $money_kzt + ($money_kzt/100)*$payy->small1; $money_kkbkz = round($money_kkbkz);

					$kkbkz_merchant_certificate_id=$payy->text4;
					$kkbkz_merchant_id=$payy->text2;
					$kkbkz_merchant_name=$payy->text1;
					$kkbkz_private_key=$payy->text6;
					$kkbkz_private_key_pass=decodePwd($payy->pass1);
					$kkbkz_public_key=$payy->text5;
					$kkbkz_shop_id=$payy->text3;
					$kkbkz_test=$payy->check1;

					if ($kkbkz_merchant_certificate_id and $kkbkz_merchant_id and $kkbkz_merchant_name and $kkbkz_private_key and $kkbkz_private_key_pass and $kkbkz_public_key and file_exists($kkbkz_private_key) and file_exists($kkbkz_public_key)) {
						$kkbkz_currency = GetCurrencyByCode("KZT");
						$kkbkz_symbol = $kkbkz_currency->symbol;

						$order_id = sprintf ("%06d",$r->id);

						$kkb = new KKBSign();
						$kkb->invert();
						$kkb->load_private_key($kkbkz_private_key, $kkbkz_private_key_pass);
						$merchant = '<merchant cert_id="'.$kkbkz_merchant_certificate_id.'" name="'.$kkbkz_merchant_name.'"><order order_id="'.$order_id.'" amount="'.$money_kkbkz.'" currency="398"><department merchant_id="'.$kkbkz_merchant_id.'" amount="'.$money_kkbkz.'"/></order></merchant>';
						$merchant_sign = '<merchant_sign type="RSA">'.$kkb->sign64($merchant).'</merchant_sign>';
						$xml = "<document>".$merchant.$merchant_sign."</document>";
						$Signed_Order_B64 = base64_encode($xml);

						$appendix = '<document><item number="1" name="Хостинговые услуги" quantity="1" amount="'.$money_kkbkz.'"/></document>';
						$appendix = base64_encode($appendix);

						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_kkbkz',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form target=_blank method="POST" style="margin: 0;" action="<? if ($kkbkz_test) { print "http://3dsecure.kkb.kz/jsp/process/logon.jsp"; } else { print "https://epay.kkb.kz/jsp/process/logon.jsp"; } ?>">
			                        <input type=hidden name=Signed_Order_B64 value="<? print $Signed_Order_B64?>">
			                        <input type=hidden name=appendix value="<? print $appendix?>">
			                        <input type=hidden name=ShopID value="<? print $kkbkz_shop_id?>">
			                        <input type=hidden name=email value="<? print $_SESSION["userEmail"]?>">
			                        <input type=hidden name=BackLink value="<? print $payment_url?>">
			                        <input type=hidden name=FailureBackLink value="<? print $nopayment_url?>">
			                        <input type=hidden name=PostLink value="<? print $full_www_path."online_kkbkz.php"?>">

			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_kkbkz?> <? print $kkbkz_symbol?>">
			                        </form>
		                        <? }
				}
				if ($payy->type == "kzmkz") {
					$money_kzmkz = $money_kzt + ($money_kzt/100)*$payy->small1; $money_kzmkz = round($money_kzmkz);

					$kzmkz_merchant_id=$payy->text1;
					$kzmkz_public_key=$payy->text2;
					$kzmkz_private_key=$payy->text3;
					$kzmkz_private_key_pass=decodePwd($payy->pass1);
					$kzmkz_test=$payy->check1;

					if ($kzmkz_merchant_id and $kzmkz_public_key and $kzmkz_private_key and $kzmkz_private_key_pass and file_exists($kzmkz_private_key) and file_exists($kzmkz_public_key)) {
						$kzmkz_currency = GetCurrencyByCode("KZT");
						$kzmkz_symbol = $kzmkz_currency->symbol;

						$data = $kzmkz_merchant_id.$r->id.$money_kzmkz."KZT";

						$priv_key = file_get_contents($kzmkz_private_key);
						$pkeyid = openssl_get_privatekey($priv_key, $kzmkz_private_key_pass);

						if (!is_resource($pkeyid)) {
							print "<font color=red>";
							while ($msg = openssl_error_string()) {
								print $msg . "<br />\n";
							}
							print "</font><BR>";
						} else {
							openssl_sign($data, $sign, $pkeyid, OPENSSL_ALGO_MD5);
							$sign = base64_encode($sign);

							@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_kzmkz',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

							 ?>
				                        <form target=_blank method="GET" style="margin: 0;" action="<? if ($kzmkz_test) { print "https://ipg.ipgroup.kz/money/payments/paymentStore.html"; } else { print "https://www.kzm.kz/payments/paymentStore.html"; } ?>">
				                        <input type=hidden name=merchantId value="<? print $kzmkz_merchant_id?>">
				                        <input type=hidden name=orderId value="<? print $r->id?>">
				                        <input type=hidden name=amount value="<? print $money_kzmkz?>">
				                        <input type=hidden name=currency value="KZT">
				                        <input type=hidden name=title value="Оплата услуг по счету № <? print $sid?>">
				                        <input type=hidden name=successUrl value="<? print $payment_url?>">
				                        <input type=hidden name=errorUrl value="<? print $nopayment_url?>">
				                        <input type=hidden name=sign value="<? print $sign?>">
				                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_kzmkz?> <? print $kzmkz_symbol?>">
				                        </form>
							<?
						}

		                        }
				}
				if ($payy->type == "copayco") {
					$copayco_currency=$payy->currency;
					if (!$copayco_currency) {$copayco_currency="UAH";}

					$copayco_currency = GetCurrencyByCode($copayco_currency);
					$money_copayco = $money*$copayco_currency->koeficient;
					$copayco_symbol = $copayco_currency->symbol;
					$copayco_code = $copayco_currency->code;

					$money_copayco = $money_copayco + ($money_copayco/100)*$payy->small1; $money_copayco = round($money_copayco,2);

					$copayco_shop_id=$payy->text1;
					$copayco_secret=decodePwd($payy->pass1);
					if ($copayco_shop_id and $copayco_secret) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_copayco',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

						$money_copayco_kop = $money_copayco*100;
						$date_time = date("Y-m-d H:i:s");
						$signature = md5($r->id.$money_copayco_kop.$copayco_code.$date_time.$copayco_secret);

					?>
						<form target=_blank method="POST" style="margin: 0;" action="https://www.copayco.com/pay.php">
						<input type="hidden" name="shop_id" value="<? print $copayco_shop_id?>">
						<input type="hidden" name="ta_id" value="<? print $r->id?>">
						<input type="hidden" name="amount" value="<? print $money_copayco_kop?>">
						<input type="hidden" name="currency" value="<? print $copayco_code?>">
						<input type="hidden" name="purpose" value="Оплата услуг по счету № <? print $sid?>">
						<input type="hidden" name="date_time" value="<? print $date_time?>">
						<input type="hidden" name="signature" value="<? print $signature?>">
						<input class=button type=submit value='<? print $_lang[Pay]?> <? print $money_copayco?> <? print $copayco_symbol?>'>
						</form>
					<? }
				}
				if ($payy->type == "qiwi") {
					$money_qiwi = $money_rub + ($money_rub/100)*$payy->small1; $money_qiwi = round($money_qiwi,2);

					$qiwi_id=$payy->text1;
					$qiwi_secret=decodePwd($payy->pass1);
					if ($qiwi_id and $qiwi_secret) {
						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_qiwi',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form target=_blank method="POST" style="margin: 0;" action="http://w.qiwi.ru/setInetBill.do" accept-charset="windows-1251">
						<input type=hidden name=txn_id value="<? print $r->id?>">
			                        <input type=hidden name=from value="<? print $qiwi_id?>">
			                        <input type=hidden name=summ value="<? print $money_qiwi?>">
						<input type=hidden name=com value="Оплата счета № <? print $sid?>">
						<input type=hidden name=lifetime value="720">
						<input type=hidden name=check_agt value="false">
						<B><? print $_lang[PayQIWIPhone]?>:</b> <input type=text class=input name=to size=15><BR><BR>
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_qiwi?> <? print $_lang[PaySokraschenieRubl]?>">
			                        </form>
		                        <? }
				}
				if ($payy->type == "monexy") {
					$monexy_id=$payy->text1;
					$monexy_secret=decodePwd($payy->pass1);
					if ($monexy_id and $monexy_secret) {
						$monexy_currency=$payy->currency;
						if (!$monexy_currency) {$monexy_currency = "UAH";}

						$monexy_currency = GetCurrencyByCode($monexy_currency);
						$money_monexy = $money*$monexy_currency->koeficient;
						$monexy_symbol = $monexy_currency->symbol;

						$money_monexy = $money_monexy + ($money_monexy/100)*$payy->small1; $money_monexy = round($money_monexy,2);

						$hash = md5($monexy_id.';'.$r->id.';'.$money_monexy.';'.$monexy_secret);

						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_monexy',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://www.monexy.com/app/mobi.php">
						<input type=hidden name="MonexyMerchantID" value="<? print $monexy_id?>">
						<input type=hidden name="MonexyMerchantInfoShopName" value="<? print $company_name?>">
						<input type=hidden name="MonexyMerchantSum" value="<? print $money_monexy?>">
						<input type=hidden name="MonexyMerchantOrderId" value="<? print $r->id?>">
						<input type=hidden name="MonexyMerchantOrderDesc" value="Оплата счета № <? print $sid?>">
						<input type=hidden name="MonexyMerchantHash" value="<? print $hash?>">
						<input type=hidden name="MonexyMerchantResultUrl" value="<? print $full_www_path."online_monexy.php"?>">
						<input type=hidden name="MonexyMerchantSuccessUrl" value="<? print $payment_url?>">
						<input type=hidden name="MonexyMerchantFailUrl" value="<? print $nopayment_url?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_monexy?> <? print $monexy_symbol?>">
			                        </form>
		                        <? }
				}
				if ($payy->type == "netcard") {
					$netcard_id=$payy->text1;
					if ($netcard_id) {
						$netcard_currency = "AZN";

						$netcard_currency = GetCurrencyByCode($netcard_currency);
						$money_netcard = $money*$netcard_currency->koeficient;
						$netcard_symbol = $netcard_currency->symbol;

						$money_netcard = round($money_netcard,2);

						if ($check and $to) {
							$netcard_desc = "bill".$sid;

							$netcard = new NETCARDAZ;
							$result = $netcard->check($netcard_id,$to,$netcard_desc);

							if ($result > 0) {
								if ($result >= $money_netcard) {
									@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_netcard',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

									if ($result > $money_netcard) {
										$money_left = $result-$money_netcard;
										$money_to_balance = $money_left/$netcard_currency->koeficient; $money_to_balance = round($money_to_balance,2);

										@mysql_query("update users set money=money+$money_to_balance where id='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

										addUserLog($_SESSION['userId'], "moneyIn", "NetCard.az: #".$to." ($money_left $netcard_symbol)");

										print $_lang[payNetCardLeftToBalanceSuccess]."<BR>";
									}

									MakeBillPayed($r->id,1,"NetCard.az ($to)");

									print $_lang[PayBillPaySuccess]."<BR><BR>";
								} else {
									$money_to_balance = $result/$netcard_currency->koeficient; $money_to_balance = round($money_to_balance,2);

									@mysql_query("update users set money=money+$money_to_balance where id='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

									addUserLog($_SESSION['userId'], "moneyIn", "NetCard.az: #".$to." ($result $netcard_symbol)");

									print $_lang[payNetCardToBalanceSuccess]."<BR><BR>";
								}
							} else {
								print "<font color=red>".$_lang[Error].": ".$netcard->error."</font><BR><BR>";
							}

						} else {
							print $_lang[payNetCardComment]."<BR><BR>";

							?>
							<form action="billing.php" method="POST">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="netcard">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
				                        <input type=hidden name=check value="1">
							<B><? print $_lang[payNetCardNumber]?>:</b> <input type=text class=input name=to size=15><BR><BR>
							<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_netcard?> <? print $netcard_symbol?>">
							</form><BR>
		                        		<?
						 }
					}
				}
				if ($payy->type == "netmoney") {
					$netmoney_id=$payy->text1;
					$netmoney_nacenka = $payy->small1;

					if ($netmoney_id) {
						$netmoney_currency = "AZN";

						$netmoney_currency = GetCurrencyByCode($netmoney_currency);
						$money_netmoney = $money*$netmoney_currency->koeficient;
						$netmoney_symbol = $netmoney_currency->symbol;

						$money_netmoney = round($money_netmoney,2);

						if ($check and $to) {
							$netmoney_desc = "bill".$sid;

							$netmoney = new NETMONEYAZ;
							$result = $netmoney->check($netmoney_id,$to,$netmoney_desc);

							if ($result > 0) {
								$result = $result - ($result/100)*$netmoney_nacenka;

								if ($result >= $money_netmoney) {
									@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_netmoney',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

									if ($result > $money_netmoney) {
										$money_left = $result-$money_netmoney;
										$money_to_balance = $money_left/$netmoney_currency->koeficient; $money_to_balance = round($money_to_balance,2);

										@mysql_query("update users set money=money+$money_to_balance where id='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

										addUserLog($_SESSION['userId'], "moneyIn", "NetMoney.az: #".$to." ($money_left $netmoney_symbol)");

										print $_lang[payNetMoneyLeftToBalanceSuccess]."<BR>";
									}

									MakeBillPayed($r->id,1,"NetMoney.az ($to)");

									print $_lang[PayBillPaySuccess]."<BR><BR>";
								} else {
									$money_to_balance = $result/$netmoney_currency->koeficient; $money_to_balance = round($money_to_balance,2);

									@mysql_query("update users set money=money+$money_to_balance where id='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

									addUserLog($_SESSION['userId'], "moneyIn", "NetMoney.az: #".$to." ($result $netmoney_symbol)");

									print $_lang[payNetMoneyToBalanceSuccess]."<BR><BR>";
								}
							} else {
								print "<font color=red>".$_lang[Error].": ".$netmoney->error."</font><BR><BR>";
							}

						} else {
							print $_lang[payNetMoneyComment]."<BR><BR>";

							?>
							<form action="billing.php" method="POST">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="netmoney">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
				                        <input type=hidden name=check value="1">
							<B><? print $_lang[payNetMoneyNumber]?>:</b> <input type=text class=input name=to size=15><BR><BR>
							<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_netmoney?> <? print $netmoney_symbol?>">
							</form><BR>
		                        		<?
						 }
					}
				}
				if ($payy->type == "sprypay") {
					$sprypay_id=$payy->text1;
					$sprypay_tab=$payy->select1;
					$sprypay_secret=decodePwd($payy->pass1);
					if ($sprypay_id and $sprypay_secret) {
						$sprypay_currency=$payy->currency;
						if (!$sprypay_currency) {$sprypay_currency = "USD";}

						if ($sprypay_currency == "RUB") { $currency = "rur"; } else { $currency = strtolower($sprypay_currency); }
						if ($_SESSION["userLang"] == "russian" or $_SESSION["userLang"] == "ukrainian") { $userLng = "ru"; } else { $userLng = "en"; }

						$sprypay_currency = GetCurrencyByCode($sprypay_currency);
						$money_sprypay = $money*$sprypay_currency->koeficient;
						$sprypay_symbol = $sprypay_currency->symbol;

						$money_sprypay = $money_sprypay + ($money_sprypay/100)*$payy->small1; $money_sprypay = round($money_sprypay,2);

						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_sprypay',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://sprypay.ru/sppi/" accept-charset="windows-1251">
						<input type=hidden name="spShopId" value="<? print $sprypay_id?>">
						<input type=hidden name="spShopPaymentId" value="<? print $r->id?>">
						<input type=hidden name="spAmount" value="<? print $money_sprypay?>">
						<input type=hidden name="spCurrency" value="<? print $currency?>">
						<input type=hidden name="spPurpose" value="bill <? print $sid?>">
						<input type=hidden name="spUserEmail" value="<? print $_SESSION["userEmail"]?>">
						<input type=hidden name="lang" value="<? print $userLng?>">
						<input type=hidden name="tabNum" value="<? print $sprypay_tab?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_sprypay?> <? print $sprypay_symbol?>">
			                        </form>
		                        <? }
				}
				if ($payy->type == "liberty") {
					$liberty_id=$payy->text1;
					$liberty_store=$payy->text2;
					$liberty_secret=decodePwd($payy->pass1);
					if ($liberty_id and $liberty_store and $liberty_secret) {
						$liberty_currency=$payy->currency;
						if (!$liberty_currency) {$liberty_currency = "USD";}

						if ($liberty_currency == "EUR") { $currency = "LREUR"; } else { $currency = "LRUSD"; }

						$liberty_currency = GetCurrencyByCode($liberty_currency);
						$money_liberty = $money*$liberty_currency->koeficient;
						$liberty_symbol = $liberty_currency->symbol;

						$money_liberty = $money_liberty + ($money_liberty/100)*$payy->small1; $money_liberty = round($money_liberty,2);

						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_liberty',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form target=_blank method="POST" style="margin: 0;" action="https://sci.libertyreserve.com/">
						<input type=hidden name="lr_acc" value="<? print $liberty_id?>">
						<input type=hidden name="lr_store" value="<? print $liberty_store?>">
						<input type=hidden name="lr_amnt" value="<? print $money_liberty?>">
						<input type=hidden name="lr_currency" value="<? print $currency?>">
						<input type=hidden name="lr_comments" value="<? print $company_name?>: bill <? print $sid?>">
						<input type=hidden name="lr_merchant_ref" value="<? print $r->id?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_liberty?> <? print $liberty_symbol?>">
			                        </form>
		                        <? }
				}
				if ($payy->type == "onpay") {
					$onpay_login=$payy->text1;
					$onpay_secret=decodePwd($payy->pass1);
					$onpay_convert=$payy->check1;
					$onpay_pricefinal=$payy->check2;
					if ($onpay_login and $onpay_secret) {
						$USDArray = array("LIZ","LRU","PPL","USD","WMZ");
						$RUBArray = array("ATM","BOC","BTR","BVC","CCT","DMR","EVS","HBK","JKH","LIQ","MBZ","MG1","MMR","MOB","MT1","NVP","OCE","OSP","POT","PRV","RBK","TCR","TRS","UNR","WMR","YDX");
						$EURArray = array("EUR","LIE","WME");
						$UAHArray = array("LIU","WMU");
						$BYRArray = array("WMB");

						if (@in_array($payid, $USDArray)) { $onpay_currency = "USD"; }
						else if (@in_array($payid, $RUBArray)) { $onpay_currency = "RUB"; }
						else if (@in_array($payid, $EURArray)) { $onpay_currency = "EUR"; }
						else if (@in_array($payid, $UAHArray)) { $onpay_currency = "UAH"; }
						else if (@in_array($payid, $BYRArray)) { $onpay_currency = "BYR"; }

						if ($_SESSION["userLang"] == "russian" or $_SESSION["userLang"] == "ukrainian") { $userLng = "ru"; } else { $userLng = "en"; }
						if ($onpay_convert) { $convert = "yes"; } else { $convert = "no"; }
						if ($onpay_pricefinal) { $pricefinal = "true"; } else { $pricefinal = ""; }
						
						if (!$onpay_currency) { $onpay_currency = "RUB"; }
						$onpay_currency = GetCurrencyByCode($onpay_currency);
						$money_onpay = $money*$onpay_currency->koeficient;
						$onpay_symbol = $onpay_currency->symbol;

						$money_onpay = $money_onpay + ($money_onpay/100)*$payy->small1; $money_onpay = round($money_onpay,2);

						$money_converted = @mb_split("\.",$money_onpay);
						if (@mb_strlen($money_converted[1]) == 0) { $money_converted = $money_onpay.".0"; }
						else if (@mb_strlen($money_converted[1]) == 1) { $money_converted = $money_onpay."0"; }
						else { $money_converted = $money_onpay; }

						$md5 = md5("fix;$money_converted;$payid;$r->id;$convert;$onpay_secret");

						@mysql_query("update bills set paymentSystemId='$payy->id',merchantmoney='$money_onpay',merchantType='' where id='$r->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); ?>

			                        <form target=_blank method="GET" style="margin: 0;" action="http://secure.onpay.ru/pay/<? print $onpay_login?>">
						<input type=hidden name="pay_mode" value="fix">
						<input type=hidden name="price" value="<? print $money_converted?>">
						<input type=hidden name="currency" value="<? print $payid?>">
						<input type=hidden name="one_way" value="<? print $payid?>">
						<input type=hidden name="pay_for" value="<? print $r->id?>">
						<input type=hidden name="convert" value="<? print $convert?>">
						<input type=hidden name="pricefinal" value="<? print $pricefinal?>">
						<input type=hidden name="f" value="7">
						<input type=hidden name="md5" value="<? print $md5?>">
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_onpay?> <? print $onpay_symbol?>">
			                        </form>
		                        <? }
				}

				print "<BR><BR>".$_lang[PayAlsoYouCan]." <A class=rootlink href=billing.php?do=$do&id=$id>".$_lang[PaySelectAnotherPayMethod]."</a>."; 

				foot('utf-8');
				mclose();
				exit;
			}

			$profile = GetUserProfileByUserId($_SESSION["userId"]);
			$allowedPayments = @mb_split(":x:",$user->allowedPayments);
			$disallowedPayments = @mb_split(":x:",$user->disallowedPayments);

			print "<Table class='rpTableBlank' border=0 cellpadding=0 cellspacing=0><tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>";

			$payys = @mysql_query("select * from pay_systems WHERE active='1' order by sort,id") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error()); 
			while ($payy = @mysql_fetch_object($payys)) {
				$payy->comment = htmlDecode($payy->comment);

				if ($payy->type == "balance" and !$workWithoutAuth and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                if (GetSetting("userBalanceEnable") and !$r->addfunds and GetUserMoney($_SESSION["userId"]) >= $money) {
	                        		?>
							<tr>
							<td valign=top><BR>&nbsp;&nbsp;&nbsp;&nbsp;<b style="font-size: 30">>>></b></td>
        		        		        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payBalance]?></B><BR><BR>
					
							<B><? print $_lang[PayNaSchetu]?>:</b>  <? print round(GetUserMoney($_SESSION["userId"])*CURK,2)?> <? print CURS?><BR><BR>

							<form method=post>
							<input type=hidden name=sub value=balancePay>
							<input type=hidden name=paymentId value=<? print $payy->id?>>
							<input class=button type=submit value="<? print $_lang[Pay]?> <? print round($money*CURK,2)?> <? print CURS?>">
							</form>
							<BR>
						
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "partner" and !$workWithoutAuth and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                if (GetSetting("partnerEnable") and GetSetting("partnerEnablePayOrders") and GetUserPartnerMoney($_SESSION["userId"]) >= $money) {
			                        ?>
							<tr>
							<td valign=top><BR>&nbsp;&nbsp;&nbsp;&nbsp;<b style="font-size: 30">>>></b></td>
		        		                <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payPartner]?></B><BR><BR>
					
							<B><? print $_lang[PayNaSchetu]?>:</b>  <? print round(GetUserPartnerMoney($_SESSION["userId"])*CURK,2)?> <? print CURS?><BR><BR>

							<form method=post>
							<input type=hidden name=sub value=partnerPay>
							<input type=hidden name=paymentId value=<? print $payy->id?>>
							<input class=button type=submit value="<? print $_lang[Pay]?> <? print round($money*CURK,2)?> <? print CURS?>">
							</form>
							<BR>
						
				                        </td></tr>
				                        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
			                        <?
			                }
				}
				else if ($payy->type == "webmoney" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
	        		        $wmz=$payy->text1;
			                $wmr=$payy->text2;
	        		        $wmu=$payy->text3;
			                $wme=$payy->text4;
	        		        $wmb=$payy->text5;
			                $wmsecret=decodePwd($payy->pass1);
					$wm_comment=$payy->comment;
			                if ($wmz or $wmr or $wmu or $wme or $wmb) {
						$money_wmz = $money_usd + ($money_usd/100)*$payy->small1; $money_wmz = round($money_wmz,2);
						$money_wmr = $money_rub + ($money_rub/100)*$payy->small2; $money_wmr = round($money_wmr,2);
						$money_wmu = $money_uah + ($money_uah/100)*$payy->small3; $money_wmu = round($money_wmu,2);
						$money_wme = $money_eur + ($money_eur/100)*$payy->small4; $money_wme = round($money_wme,2);
						$money_wmb = $money_byr + ($money_byr/100)*$payy->small5; $money_wmb = round($money_wmb,2);

			                        ?>
	                        		<tr>
	        		                <td valign=top><BR><A class=rootlink href="http://www.webmoney.ru/" target=_blank><img src="./_rootimages/logo_wm.gif" border=0></a></td>
			                        <td width=10>&nbsp;</td>
									<td width=1 bgcolor=#CCCCCC></td>
									<td width=10>&nbsp;</td>
			                        <td><BR>
	                        
	                        		<B>:: <? print $_lang[payWebMoney]?></b><BR><BR>

						<? if ($wm_comment) {print $wm_comment."<BR><BR>";} ?>
	                        
			                        <B><? print $_lang[PayPurses]?>:</B><BR>
	                        		<? if ($wmz) { print "WMZ: $wmz (".$_lang[PayToPay]." $money_wmz WMZ)<BR>"; } ?>
	        		                <? if ($wmr) { print "WMR: $wmr (".$_lang[PayToPay]." $money_wmr WMR)<BR>"; } ?>
			                        <? if ($wmu) { print "WMU: $wmu (".$_lang[PayToPay]." $money_wmu WMU)<BR>"; } ?>
	                        		<? if ($wme) { print "WME: $wme (".$_lang[PayToPay]." $money_wme WME)<BR>"; } ?>
	        		                <? if ($wmb) { print "WMB: $wmb (".$_lang[PayToPay]." $money_wmb WMB)<BR>"; } ?>
			                        <BR>
	
						<? if ($wmz and $wmsecret) { ?>
	        		                <form method="POST" style="margin: 0;" action="billing.php">
			                        <input type=hidden name=do value="<? print $do?>">
	                        		<input type=hidden name=sub value="gotomerchant">
	        		                <input type=hidden name=id value="<? print $id?>">
			                        <input type=hidden name=paytype value="wmz">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
	                        		<input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_wmz?> WMZ">
	        		                </form>
						<? } ?>

						<? if ($wmr and $wmsecret) { ?>
			                        <form method="POST" style="margin: 0;" action="billing.php">
	                        		<input type=hidden name=do value="<? print $do?>">
	        		                <input type=hidden name=sub value="gotomerchant">
			                        <input type=hidden name=id value="<? print $id?>">
	                        		<input type=hidden name=paytype value="wmr">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
	        		                <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_wmr?> WMR">
			                        </form>
						<? } ?>
	
						<? if ($wmu and $wmsecret) { ?>
			                        <form method="POST" style="margin: 0;" action="billing.php">
	                        		<input type=hidden name=do value="<? print $do?>">
	        		                <input type=hidden name=sub value="gotomerchant">
			                        <input type=hidden name=id value="<? print $id?>">
	                        		<input type=hidden name=paytype value="wmu">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
	        		                <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_wmu?> WMU">
			                        </form>
						<? } ?>


						<? if ($wme and $wmsecret) { ?>
			                        <form method="POST" style="margin: 0;" action="billing.php">
	                        		<input type=hidden name=do value="<? print $do?>">
	        		                <input type=hidden name=sub value="gotomerchant">
			                        <input type=hidden name=id value="<? print $id?>">
	                        		<input type=hidden name=paytype value="wme">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
	        		                <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_wme?> WME">
			                        </form>
						<? } ?>
	
						<? if ($wmb and $wmsecret) { ?>
			                        <form method="POST" style="margin: 0;" action="billing.php">
	                        		<input type=hidden name=do value="<? print $do?>">
	        		                <input type=hidden name=sub value="gotomerchant">
			                        <input type=hidden name=id value="<? print $id?>">
	                        		<input type=hidden name=paytype value="wmb">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
	        		                <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_wmb?> WMB">
			                        </form>
						<? } ?>

						<? if ($wmr and $wmsecret) { ?>
			                        <form method="POST" style="margin: 0;" action="billing.php">
	                        		<input type=hidden name=do value="<? print $do?>">
	        		                <input type=hidden name=sub value="gotomerchant">
			                        <input type=hidden name=id value="<? print $id?>">
	                        		<input type=hidden name=paytype value="wmr">
	                        		<input type=hidden name=paysubtype value="card">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
	        		                <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_wmr?> <? print $_lang[PaySokraschenieRubl]?> <? print $_lang[PayRussianCard]?>">
			                        </form>
						<? } ?>
	                        
						<BR>
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "superlend" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
	        		        $wmz=$payy->text1;
			                $wmsecret=decodePwd($payy->pass1);
					$wm_comment=$payy->comment;
			                if ($wmz and $wmsecret) {
						$money_wmz = $money_usd + ($money_usd/100)*$payy->small1; $money_wmz = round($money_wmz,2);

			                        ?>
	                        		<tr>
	        		                <td valign=top><BR><A class=rootlink href="http://www.superlend.ru/" target=_blank><img src="./_rootimages/logo_superlend.gif" border=0></a></td>
			                        <td width=10>&nbsp;</td>
									<td width=1 bgcolor=#CCCCCC></td>
									<td width=10>&nbsp;</td>
			                        <td><BR>
	                        
	                        		<B>:: <? print $_lang[paySuperLend]?></b><BR><BR>

						<? if ($wm_comment) {print $wm_comment."<BR><BR>";} ?>
	                        
	        		                <form method="POST" style="margin: 0;" action="billing.php">
			                        <input type=hidden name=do value="<? print $do?>">
	                        		<input type=hidden name=sub value="gotomerchant">
	        		                <input type=hidden name=id value="<? print $id?>">
			                        <input type=hidden name=paytype value="superlend">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
	                        		<input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_wmz?> WMZ">
	        		                </form>

						<BR>
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "perfectmoney" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
	        		        $perfect_id = $payy->text1;
			                $perfect_pass = decodePwd($payy->pass1);
					$perfect_comment = $payy->comment;
			                if ($perfect_id and $perfect_pass) {
						$money_perfect = $money_usd + ($money_usd/100)*$payy->small1; $money_perfect = round($money_perfect,2);

			                        ?>
	                        		<tr>
	        		                <td valign=top><BR><A class=rootlink href="http://www.perfectmoney.com/" target=_blank><img src="./_rootimages/logo_perfectmoney.gif" border=0></a></td>
			                        <td width=10>&nbsp;</td>
						<td width=1 bgcolor=#CCCCCC></td>
						<td width=10>&nbsp;</td>
			                        <td><BR>
	                        
	                        		<B>:: <? print $_lang[payPerfectMoney]?></b><BR><BR>

						<? if ($perfect_comment) {print $perfect_comment."<BR><BR>";} ?>
	                        
	        		                <form method="POST" style="margin: 0;" action="billing.php">
			                        <input type=hidden name=do value="<? print $do?>">
	                        		<input type=hidden name=sub value="gotomerchant">
	        		                <input type=hidden name=id value="<? print $id?>">
			                        <input type=hidden name=paytype value="perfectmoney">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
	                        		<input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_perfect?> $">
	        		                </form>

						<BR>
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "yandex" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $yandex=$payy->text1;
					$yandex_comment=$payy->comment;
					$yandex_client_id = $payy->text2;
					$yandex_redirect_uri = $payy->text3;
					$yandex_client_secret = $payy->text4;
					$yandex_access_token = $payy->text5;
					$yandex_notify_secret = $payy->text6;

			                if ($yandex) {
						$money_yandex = $money_rub + ($money_rub/100)*$payy->small1; $money_yandex = round($money_yandex,2);
	        		                ?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://money.yandex.ru/" target=_blank><img src="./_rootimages/logo_yandex.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
       					                <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payYandex]?></B><BR><BR>

							<? if ($yandex_comment) {print $yandex_comment."<BR><BR>";} ?>
								
							<B><? print $_lang[PayPurse]?>:</b> <? print $yandex?> (<? print $_lang[PayToPay]?> <? print $money_yandex?> <? print $_lang[PaySokraschenieRubl]?>)<BR><BR>
							
		                		        <form method="POST" action="billing.php">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="yandex">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
				                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_yandex?> <? print $_lang[PaySokraschenieRubl]?>">
		                		        </form>

							<? if (!($yandex_access_token and $yandex_client_id and $yandex_notify_secret)) { ?>
								<b style="font-size: 14;"><? print $_lang[PayPayByHandsAlert]?></b><br><Br>
							<? } ?>
                        
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "pro" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
	        		        $pro_client=$payy->text1;
			                $pro_ra=$payy->text2;
	        		        $pro_secret=decodePwd($payy->pass1);
					$pro_comment=$payy->comment;
	        		        if ($pro_client and $pro_ra and $pro_secret) {
						$money_pro = $money_rub + ($money_rub/100)*$payy->small1; $money_pro = round($money_pro,2);
	                        		?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://www.prochange.ru/merchant.html" target=_blank><img src="./_rootimages/logo_pro.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
       			        		        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payYandex]?></B><BR><BR>

							<? if ($pro_comment) {print $pro_comment."<BR><BR>";} ?>
						
		                		        <form method="POST" action="billing.php">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="pro">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
				                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_pro?> <? print $_lang[PaySokraschenieRubl]?>">
		                		        </form>

							<Br>
                        
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "egold" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $egold=$payy->text1;
					$egold_comment=$payy->comment;
			                if ($egold) {
						$money_egold = $money_usd + ($money_usd/100)*$payy->small1; $money_egold = round($money_egold,2);
	        		                ?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://www.e-gold.com/" target=_blank><img src="./_rootimages/logo_egold.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				                        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payEgold]?></b><BR><BR>

							<? if ($egold_comment) {print $egold_comment."<BR><BR>";} ?>
								
							<B><? print $_lang[PayAccount]?>:</B> <? print $egold?> (<? print $_lang[PayToPay]?> <? print $money_egold?> $)<BR><BR>
						
		                		        <form action="billing.php" method="POST">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="egold">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
				                        <input class=button type="submit" name="PAYMENT_METHOD" value="<? print $_lang[Pay]?> <? print $money_egold?> $">
		                		        </form>
			
							<BR>
                        
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "rupay" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $rupayschet=$payy->text1;
	        		        $rupay=$payy->text2;
			                $rupaysecret=decodePwd($payy->pass1);
					$rupay_comment=$payy->comment;
			                if ($rupayschet) {
						$money_rupay = $money_rub + ($money_rub/100)*$payy->small1; $money_rupay = round($money_rupay,2);
	        		                ?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://rbkmoney.ru/" target=_blank><img src="./_rootimages/logo_rbk.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				                        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payRBKMoney]?></b><BR><BR>

							<? if ($rupay_comment) {print $rupay_comment."<BR><BR>";} ?>
						
							<B><? print $_lang[PayBill]?>:</B> <? print $rupayschet?> (<? print $_lang[PayToPay]?> <? print $money_rupay?> <? print $_lang[PaySokraschenieRubl]?>)<BR><BR>
						
							<? if ($rupay and $rupaysecret) { ?>
							<form action="billing.php" >
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="rupay">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
							<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_rupay?> <? print $_lang[PaySokraschenieRubl]?>">
							</form><BR>
				                        <? } ?>
                        
     
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "zpay" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
	        		        $zpay=$payy->text1;
			                $zpayid=$payy->text2;
	        		        $zpaysecret=decodePwd($payy->pass1);
					$zpay_comment=$payy->comment;
	        		        if ($zpay) {
						$money_zpay = $money_rub + ($money_rub/100)*$payy->small1; $money_zpay = round($money_zpay,2);

	                        		?>
	        		                <tr>
			                        <td valign=top><BR><A class=rootlink href="http://www.z-payment.ru/" target=_blank><img src="./_rootimages/logo_zpay.gif" border=0></a></td>
	                        		<td width=10>&nbsp;</td>
									<td width=1 bgcolor=#CCCCCC></td>
									<td width=10>&nbsp;</td>
	                        		<td><BR>
	                        
	        		                <B>:: <? print $_lang[payZPayment]?></b><BR><BR>

						<? if ($zpay_comment) {print $zpay_comment."<BR><BR>";} ?>
	                        
	                        		<B><? print $_lang[PayPurse]?>:</B> <? print $zpay?> (<? print $_lang[PayToPay]?> <? print $money_zpay?> ZP)<BR><BR>
	
						<? if ($zpaysecret and $zpayid) { ?>
			                        <form method="POST" style="margin: 0;" action="billing.php">
                	        		<input type=hidden name=do value="<? print $do?>">
        			                <input type=hidden name=sub value="gotomerchant">
			                        <input type=hidden name=id value="<? print $id?>">
	                        		<input type=hidden name=paytype value="zpay">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
	        		                <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_zpay?> ZP">
			                        </form>
						<? } ?>
						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "robox" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $robox=$payy->text1;
	        		        $robox_pass1=decodePwd($payy->pass1);
					$robox_currency=$payy->currency;
					$robox_comment=$payy->comment;
			                if ($robox and $robox_pass1) {
						if ($robox_currency) {
							$robox_currency = GetCurrencyByCode($robox_currency);
							$money_robox = $money*$robox_currency->koeficient;
							$robox_symbol = $robox_currency->symbol;
						} else {
							$money_robox = $money_usd;
							$robox_symbol = "\$";
						}
						$money_robox = $money_robox + ($money_robox/100)*$payy->small1; $money_robox = round($money_robox,2);

	                        		?>
	        		                <tr>
			                        <td valign=top><BR><A class=rootlink href="http://www.roboxchange.com/" target=_blank><img src="./_rootimages/logo_robox.gif" border=0></a></td>
	                        		<td width=10>&nbsp;</td>
									<td width=1 bgcolor=#CCCCCC></td>
									<td width=10>&nbsp;</td>
	                        		<td><BR>
	                        
	        		                <B>:: <? print $_lang[payRoboxchange]?></b><BR><BR>

						<? if ($robox_comment) {print $robox_comment."<BR><BR>";} ?>
	                        
						<form method="POST" style="margin: 0;" action="billing.php">
                			        <input type=hidden name=do value="<? print $do?>">
		        	                <input type=hidden name=sub value="gotomerchant">
	                        		<input type=hidden name=id value="<? print $id?>">
	        		                <input type=hidden name=paytype value="robox">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
						<input class=button type=submit value='<? print $_lang[Pay]?> <? print $money_robox?> <? print $robox_symbol?>'>
						</form>

						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "ikass" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
	        		        $ikass_id=$payy->text1;
			                $ikass_pass=decodePwd($payy->pass1);
					$ikass_select1=$payy->select1; if ($ikass_select1) { $ikass_select1 = @mb_split(":x:", $ikass_select1); } else { $ikass_select1 = array(); }
					$ikass_comment=$payy->comment;
			                if ($ikass_id and $ikass_pass) {
						$money_ikass = $money_usd + ($money_usd/100)*$payy->small1; $money_ikass = round($money_ikass,2);

	        		                ?>
			                        <tr>
	        		                <td valign=top><BR><A class=rootlink href="http://www.interkassa.com/" target=_blank><img src="./_rootimages/logo_ikass.gif" border=0></a></td>
			                        <td width=10>&nbsp;</td>
									<td width=1 bgcolor=#CCCCCC></td>
									<td width=10>&nbsp;</td>
			                        <td><BR>
	                        
	                		        <B>:: <? print $_lang[payInterkassa]?></b><BR><BR>
	        	
						<? if ($ikass_comment) {print $ikass_comment."<BR><BR>";} ?>
	                        
	                        		<form method="POST" style="margin: 0;" action="billing.php">
                			        <input type=hidden name=do value="<? print $do?>">
		        	                <input type=hidden name=sub value="gotomerchant">
	                        		<input type=hidden name=id value="<? print $id?>">
	        		                <input type=hidden name=paytype value="ikass">
						<input type=hidden name=paymentId value=<? print $payy->id?>>

						<? if (@count($ikass_select1) > 0) { ?>
							<? print $_lang[PayInterkassaSelect]?>:</b><BR>
							<select name="payid" class="input">
							<? 
							while (list($sid,$sname) = @each($_pays[ikass][select1checks])) {
								if (@in_array($sid,$ikass_select1)) { print "<option value=\"$sid\">$sname</option>"; }
							}
							?>
							</select><BR><BR>
						<? } ?>

			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_ikass?> $">
	                        		</form>
						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "easypay" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $easypay_merno=$payy->text1;
	        		        $easypay_pass=decodePwd($payy->pass1);
					$easypay_comment=$payy->comment;
	        		        if ($easypay_merno and $easypay_pass) {
						$money_easypay = $money_byr + ($money_byr/100)*$payy->small1; $money_easypay = ceil($money_easypay);

	                        		?>
	        		                <tr>
			                        <td valign=top><BR><A class=rootlink href="http://www.easypay.by/" target=_blank><img src="./_rootimages/logo_easypay.gif" border=0></a></td>
	                        		<td width=10>&nbsp;</td>
									<td width=1 bgcolor=#CCCCCC></td>
									<td width=10>&nbsp;</td>
	                        		<td><BR>
	                        
	        		                <B>:: <? print $_lang[payEasyPay]?></b><BR><BR>

						<? if ($easypay_comment) {print $easypay_comment."<BR><BR>";} ?>
	                        
						<? if ($money_easypay >= 105) { ?>
				                        <form method="POST" style="margin: 0;" action="billing.php">
        	        			        <input type=hidden name=do value="<? print $do?>">
        				                <input type=hidden name=sub value="gotomerchant">
			                	        <input type=hidden name=id value="<? print $id?>">
	                        			<input type=hidden name=paytype value="easypay">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
							<? print $_lang[PayInputEasyPayPurse]?>:<BR>
							<input class=input type=text name=purse size=10> <input class=button type=submit value="<? print $_lang[PayCreateBillForSumm]?> <? print $money_easypay?> <? print $_lang[PaySokraschenieRubl]?>">
	        	        		        </form>
						<? } else { ?>
							<? print $_lang[PayMinBillSummEasyPay]?><BR>
						<? } ?>
						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "webpay" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
	        		        $webpay_storeid=$payy->text1;
			                $webpay_secret=decodePwd($payy->pass1);
					$webpay_comment=$payy->comment;
			                if ($webpay_storeid and $webpay_secret) {
						$money_webpay = $money_byr + ($money_byr/100)*$payy->small1; $money_webpay = ceil($money_webpay);

			                        ?>
	                        		<tr>
	        		                <td valign=top><BR><A class=rootlink href="http://www.webpay.by/" target=_blank><img src="./_rootimages/logo_webpay.gif" border=0></a></td>
			                        <td width=10>&nbsp;</td>
									<td width=1 bgcolor=#CCCCCC></td>
									<td width=10>&nbsp;</td>
	                        		<td><BR>
	                        
	        		                <B>:: <? print $_lang[payWebPay]?></b><BR><BR>

						<? if ($webpay_comment) {print $webpay_comment."<BR><BR>";} ?>
	                        
	                        		<form method="POST" style="margin: 0;" action="billing.php">
       	        			        <input type=hidden name=do value="<? print $do?>">
		       		                <input type=hidden name=sub value="gotomerchant">
                	        		<input type=hidden name=id value="<? print $id?>">
                		        	<input type=hidden name=paytype value="webpay">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
						<input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_webpay?> <? print $_lang[PaySokraschenieRubl]?>">
        	                		</form>
						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "portmone" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
	        		        $portmone_id=$payy->text1;
			                $portmone_login=$payy->text2;
	        		        $portmone_password=decodePwd($payy->pass1);
					$portmone_comment=$payy->comment;
	        		        if ($portmone_id and $portmone_login and $portmone_password) {
						$money_portmone = $money_uah + ($money_uah/100)*$payy->small1; $money_portmone = round($money_portmone,2);

	                        		?>
	        		                <tr>
			                        <td valign=top><BR><A class=rootlink href="http://www.portmone.com/" target=_blank><img src="./_rootimages/logo_portmone.gif" border=0></a></td>
	                        		<td width=10>&nbsp;</td>
									<td width=1 bgcolor=#CCCCCC></td>
									<td width=10>&nbsp;</td>
	                        		<td><BR>
	                        
	        		                <B>:: <? print $_lang[PayPortmone]?></b><BR><BR>

						<? if ($portmone_comment) {print $portmone_comment."<BR><BR>";} ?>
	                        
	                        		<form method="POST" style="margin: 0;" action="billing.php">
       	        			        <input type=hidden name=do value="<? print $do?>">
		       		                <input type=hidden name=sub value="gotomerchant">
                	        		<input type=hidden name=id value="<? print $id?>">
                		        	<input type=hidden name=paytype value="portmone">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_portmone?> <? print $_lang[PaySokraschenieGrivna]?>">
        	                		</form>
						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "payu" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
	        		        $payu_id=$payy->text1;
	        		        $payu_secret=decodePwd($payy->pass1);
					$payu_comment=$payy->comment;
	        		        if ($payu_id and $payu_secret) {
						$money_payu = $money_uah + ($money_uah/100)*$payy->small1; $money_payu = round($money_payu,2);

	                        		?>
	        		                <tr>
			                        <td valign=top><BR><A class=rootlink href="http://www.payu.ua/" target=_blank><img src="./_rootimages/logo_payu.gif" border=0></a></td>
	                        		<td width=10>&nbsp;</td>
									<td width=1 bgcolor=#CCCCCC></td>
									<td width=10>&nbsp;</td>
	                        		<td><BR>
	                        
	        		                <B>:: <? print $_lang[payPayU]?></b><BR><BR>

						<? if ($payu_comment) {print $payu_comment."<BR><BR>";} ?>
	                        
	                        		<form method="POST" style="margin: 0;" action="billing.php">
       	        			        <input type=hidden name=do value="<? print $do?>">
		       		                <input type=hidden name=sub value="gotomerchant">
                	        		<input type=hidden name=id value="<? print $id?>">
                		        	<input type=hidden name=paytype value="payu">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_payu?> <? print $_lang[PaySokraschenieGrivna]?>">
        	                		</form>
						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "privatbank" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
	        		        $privatbank_id=$payy->text1;
			                $privatbank_name=$payy->text2;
	        		        $privatbank_pass=decodePwd($payy->pass1);
					$privatbank_comment=$payy->comment;
	        		        if ($privatbank_id and $privatbank_name and $privatbank_pass) {
						$money_privatbank = $money_uah + ($money_uah/100)*$payy->small1; $money_privatbank = round($money_privatbank,2);

	                        		?>
	        		                <tr>
			                        <td valign=top><BR><A class=rootlink href="http://shop.privatbank.ua/" target=_blank><img src="./_rootimages/logo_privatbank.gif"  border=0></a></td>
	                        		<td width=10>&nbsp;</td>
									<td width=1 bgcolor=#CCCCCC></td>
									<td width=10>&nbsp;</td>
	                        		<td><BR>
	                        
	        		                <B>:: <? print $_lang[PayPrivatbank]?></b><BR><BR>

						<? if ($privatbank_comment) {print $privatbank_comment."<BR><BR>";} ?>
	                        
	                        		<form method="POST" style="margin: 0;" action="billing.php">
       	        			        <input type=hidden name=do value="<? print $do?>">
		       		                <input type=hidden name=sub value="gotomerchant">
                	        		<input type=hidden name=id value="<? print $id?>">
                		        	<input type=hidden name=paytype value="privatbank">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_privatbank?> <? print $_lang[PaySokraschenieGrivna]?>">
        	                		</form>
						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "p24" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $p24_id=$payy->text1;
	        		        $p24_pass=decodePwd($payy->pass1);
			                $p24_currency=$payy->currency;
					$p24_comment=$payy->comment;
			                if ($p24_id and $p24_pass) {
						if (!$p24_currency) {$p24_currency = "UAH";}

						$p24_currency = GetCurrencyByCode($p24_currency);
						$money_p24 = $money*$p24_currency->koeficient;
						$p24_symbol = $p24_currency->symbol;

						$money_p24 = $money_p24 + ($money_p24/100)*$payy->small1; $money_p24 = round($money_p24,2);

			                        ?>
	                	        	<tr>
	        	        	        <td valign=top><BR><A class=rootlink href="http://www.privatbank.ua/" target=_blank><img src="./_rootimages/logo_p24.gif"  border=0></a></td>
			                        <td width=10>&nbsp;</td>
									<td width=1 bgcolor=#CCCCCC></td>
									<td width=10>&nbsp;</td>
			                        <td><BR>
	                        
	                		        <B>:: <? print $_lang[PayP24]?></b><BR><BR>

						<? if ($p24_comment) {print $p24_comment."<BR><BR>";} ?>
                        
		                        	<form method="POST" style="margin: 0;" action="billing.php">
       	        			        <input type=hidden name=do value="<? print $do?>">
		       		                <input type=hidden name=sub value="gotomerchant">
                	        		<input type=hidden name=id value="<? print $id?>">
                		        	<input type=hidden name=paytype value="p24">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_p24?> <? print $p24_symbol?>">
        	                		</form>
						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "liqpay" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $liqpay_id=$payy->text1;
	        		        $liqpay_password=decodePwd($payy->pass1);
			                $liqpay_currency=$payy->currency;
					$liqpay_comment=$payy->comment;
			                if ($liqpay_id and $liqpay_password) {
						if ($liqpay_currency) {
							$liqpay_currency = GetCurrencyByCode($liqpay_currency);
							$money_liqpay = $money*$liqpay_currency->koeficient;
							$liqpay_symbol = $liqpay_currency->symbol;
						} else {
							$money_liqpay = $money_usd;
							$liqpay_symbol = "\$";
						}
						$money_liqpay_card = $money_liqpay + ($money_liqpay/100)*$payy->small1; $money_liqpay_card = round($money_liqpay_card,2);
						$money_liqpay_phone = $money_liqpay + ($money_liqpay/100)*$payy->small2; $money_liqpay_phone = round($money_liqpay_phone,2);
						$money_liqpay_nalichnie = $money_liqpay + ($money_liqpay/100)*$payy->small3; $money_liqpay_nalichnie = round($money_liqpay_nalichnie,2);

	        		                ?>
			                        <tr>
	                		        <td valign=top><BR><A class=rootlink href="http://shop.privatbank.ua/" target=_blank><img src="./_rootimages/logo_liqpay.gif"  border=0></a></td>
			                        <td width=10>&nbsp;</td>
						<td width=1 bgcolor=#CCCCCC></td>
						<td width=10>&nbsp;</td>
	                        		<td><BR>
	                        
	        		                <B>:: <? print $_lang[PayLiqpay]?></b><BR><BR>

						<? if ($liqpay_comment) {print $liqpay_comment."<BR><BR>";} ?>
	                        
	                        		<form method="POST" style="margin: 0;" action="billing.php">
       	        			        <input type=hidden name=do value="<? print $do?>">
		       		                <input type=hidden name=sub value="gotomerchant">
                	        		<input type=hidden name=id value="<? print $id?>">
                		        	<input type=hidden name=paytype value="liqpay">
                		        	<input type=hidden name=payway value="card">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_liqpay_card?> <? print $liqpay_symbol?> (<? print $_lang[PayLiqpayCard]?>)">
        	                		</form>
						<BR>
	                        		<form method="POST" style="margin: 0;" action="billing.php">
       	        			        <input type=hidden name=do value="<? print $do?>">
		       		                <input type=hidden name=sub value="gotomerchant">
                	        		<input type=hidden name=id value="<? print $id?>">
                		        	<input type=hidden name=paytype value="liqpay">
                		        	<input type=hidden name=payway value="liqpay">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_liqpay_phone?> <? print $liqpay_symbol?> (<? print $_lang[PayLiqpayPhone]?>)">
        	                		</form>
						<BR>
	                        		<form method="POST" style="margin: 0;" action="billing.php">
       	        			        <input type=hidden name=do value="<? print $do?>">
		       		                <input type=hidden name=sub value="gotomerchant">
                	        		<input type=hidden name=id value="<? print $id?>">
                		        	<input type=hidden name=paytype value="liqpay">
                		        	<input type=hidden name=payway value="delayed">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_liqpay_nalichnie?> <? print $liqpay_symbol?> (<? print $_lang[PayLiqpayNalichnie]?>)">
        	                		</form>
						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "moneyua" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $moneyua_id=$payy->text1;
	        		        $moneyua_secret=decodePwd($payy->pass1);
					$moneyua_comment=$payy->comment;
			                if ($moneyua_id and $moneyua_secret) {
						$moneyua_currency = CURC;

						$moneyua_currency = GetCurrencyByCode($moneyua_currency);
						$money_moneyua = $money*$moneyua_currency->koeficient;
						$moneyua_symbol = $moneyua_currency->symbol;

						$money_moneyua = $money_moneyua + ($money_moneyua/100)*$payy->small1; $money_moneyua = round($money_moneyua,2);

	        		                ?>
			                        <tr>
	                		        <td valign=top><BR><A class=rootlink href="http://money.ua/" target=_blank><img src="./_rootimages/logo_moneyua.gif"  border=0></a></td>
			                        <td width=10>&nbsp;</td>
						<td width=1 bgcolor=#CCCCCC></td>
						<td width=10>&nbsp;</td>
	                        		<td><BR>
	                        
	        		                <B>:: <? print $_lang[PayMoneyua]?></b><BR><BR>

						<? if ($moneyua_comment) {print $moneyua_comment."<BR><BR>";} ?>
	                        
	                        		<form method="POST" style="margin: 0;" action="billing.php">
       	        			        <input type=hidden name=do value="<? print $do?>">
		       		                <input type=hidden name=sub value="gotomerchant">
                	        		<input type=hidden name=id value="<? print $id?>">
                		        	<input type=hidden name=paytype value="moneyua">
						<input type=hidden name=paymentId value=<? print $payy->id?>>

						<B><? print $_lang[PayMoneyuaSelect]?>:</b><BR>
						<select name="payid" class="input">
						<option value="1">WMZ</option>
						<option value="2">WMR</option>
						<option value="3">WMU</option>
						<option value="5">Yandex.Деньги</option>
						<option value="9">НСМЄП</option>
						<option value="14">Терминалы приема наличных</option>
						<option value="15">LIQPAY-USD</option>
						<option value="16">LIQPAY-UAH</option>
						<option value="17">PRIVAT24-UAH</option>
						<option value="18">PRIVAT24-USD</option>
						<option value="16">VISA/MASTER Card</option>
						</select><BR><BR>

			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_moneyua?> <? print $moneyua_symbol?>">
        	                		</form>
						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "twopay" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $twopay_id=$payy->text1;
	        		        $twopay_secret=decodePwd($payy->pass1);
			                $twopay_currency=$payy->currency;
					$twopay_comment=$payy->comment;
			                if ($twopay_id and $twopay_secret) {
						if ($twopay_currency) {
							$twopay_currency = GetCurrencyByCode($twopay_currency);
							$money_twopay = $money*$twopay_currency->koeficient;
							$twopay_symbol = $twopay_currency->symbol;
						} else {
							$money_twopay = $money_usd;
							$twopay_symbol = "\$";
						}
						$money_twopay = $money_twopay + ($money_twopay/100)*$payy->small1; $money_twopay = round($money_twopay,2);

	        		                ?>
			                        <tr>
	                        		<td valign=top><BR><A class=rootlink href="http://www.2pay.ru/" target=_blank><img src="./_rootimages/logo_twopay.png"  border=0></a></td>
	        		                <td width=10>&nbsp;</td>
						<td width=1 bgcolor=#CCCCCC></td>
						<td width=10>&nbsp;</td>
			                        <td><BR>
	                        
	                        		<B>:: <? print $_lang[Pay2pay]?></b><BR><BR>

						<? if ($twopay_comment) {print $twopay_comment."<BR><BR>";} ?>
	                        
			                        <form method="POST" style="margin: 0;" action="billing.php">
       	        	        		<input type=hidden name=do value="<? print $do?>">
       				                <input type=hidden name=sub value="gotomerchant">
		                	        <input type=hidden name=id value="<? print $id?>">
                        			<input type=hidden name=paytype value="twopay">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
	        		                <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_twopay?> <? print $twopay_symbol?>">
		        	                </form>
						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "smscoin" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
	        		        $smscoin_id=$payy->text1;
			                $smscoin_secret=decodePwd($payy->pass1);
					$smscoin_comment=$payy->comment;
			                if ($smscoin_id and $smscoin_secret) {
						$money_smscoin = $money_usd + ($money_usd/100)*$payy->small1; $money_smscoin = round($money_smscoin,2);

			                        ?>
	                        		<tr>
	        		                <td valign=top><BR><A class=rootlink href="http://www.smscoin.com/" target=_blank><img src="./_rootimages/logo_smscoin.gif" border=0></a></td>
			                        <td width=10>&nbsp;</td>
						<td width=1 bgcolor=#CCCCCC></td>
						<td width=10>&nbsp;</td>
	                        		<td><BR>
	                        
	        		                <B>:: <? print $_lang[PaySMSCoin]?></b><BR><BR>

						<? if ($smscoin_comment) {print $smscoin_comment."<BR><BR>";} ?>
	                        
	                        		<form method="POST" style="margin: 0;" action="billing.php">
       	        			        <input type=hidden name=do value="<? print $do?>">
		       		                <input type=hidden name=sub value="gotomerchant">
                	        		<input type=hidden name=id value="<? print $id?>">
                		        	<input type=hidden name=paytype value="smscoin">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_smscoin?> $">
        	                		</form>
						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "paypal" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
	        		        $paypal=$payy->text1;
					$paypal_currency=$payy->currency;
					$paypal_comment=$payy->comment;
			                if ($paypal) {
						if ($paypal_currency) {
							$paypal_currency = GetCurrencyByCode($paypal_currency);
							$money_paypal = $money*$paypal_currency->koeficient;
							$paypal_symbol = $paypal_currency->symbol;
							$paypal_code = $paypal_currency->code;
						} else {
							$money_paypal = $money_usd;
							$paypal_symbol = "\$";
							$paypal_code = "USD";
						}
						$money_paypal = $money_paypal + ($money_paypal/100)*$payy->small1; $money_paypal = round($money_paypal,2);

			                        ?>
	                        		<tr>
	        		                <td valign=top><BR><A class=rootlink href="http://www.paypal.com/" target=_blank><img src="./_rootimages/logo_paypal.gif" border=0></a></td>
			                        <td width=10>&nbsp;</td>
						<td width=1 bgcolor=#CCCCCC></td>
						<td width=10>&nbsp;</td>
	                        		<td><BR>
	                        
	        		                <B>:: <? print $_lang[PayPal]?></b><BR><BR>

						<? if ($paypal_comment) {print $paypal_comment."<BR><BR>";} ?>
	                        
	                        		<form method="POST" style="margin: 0;" action="billing.php">
       	        			        <input type=hidden name=do value="<? print $do?>">
		       		                <input type=hidden name=sub value="gotomerchant">
                	        		<input type=hidden name=id value="<? print $id?>">
                		        	<input type=hidden name=paytype value="paypal">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_paypal?> <? print $paypal_symbol?>">
        	                		</form>
						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "twoco" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
	        		        $twoco_id=$payy->text1;
			                $twoco_secret=decodePwd($payy->pass1);
					$twoco_comment=$payy->comment;
			                if ($twoco_id and $twoco_secret) {
						$money_twoco = $money_usd + ($money_usd/100)*$payy->small1; $money_twoco = round($money_twoco,2);

			                        ?>
	                        		<tr>
	        		                <td valign=top><BR><A class=rootlink href="http://www.2checkout.com/" target=_blank><img src="./_rootimages/logo_twoco.gif" border=0></a><BR><BR></td>
			                        <td width=10>&nbsp;</td>
						<td width=1 bgcolor=#CCCCCC></td>
						<td width=10>&nbsp;</td>
	                        		<td valign=top><BR>
	                        
	        		                <B>:: <? print $_lang[Pay2Checkout]?></b><BR><BR>
	        
						<? if ($twoco_comment) {print $twoco_comment."<BR><BR>";} ?>
	                        
	                        		<form method="POST" style="margin: 0;" action="billing.php">
       	        			        <input type=hidden name=do value="<? print $do?>">
		       		                <input type=hidden name=sub value="gotomerchant">
                	        		<input type=hidden name=id value="<? print $id?>">
                		        	<input type=hidden name=paytype value="twoco">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_twoco?> $">
        	                		</form>
						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "authorize" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $authorize_login=$payy->text1;
	        		        $authorize_secret=decodePwd($payy->pass1);
					$authorize_comment=$payy->comment;
			                if ($authorize_login and $authorize_secret) {
						$money_authorize = $money_usd + ($money_usd/100)*$payy->small1; $money_authorize = round($money_authorize,2);

			                        ?>
	                        		<tr>
	        		                <td valign=top><BR><A class=rootlink href="http://www.authorize.net/" target=_blank><img src="./_rootimages/logo_authorize.gif" border=0></a><BR><BR></td>
			                        <td width=10>&nbsp;</td>
						<td width=1 bgcolor=#CCCCCC></td>
						<td width=10>&nbsp;</td>
	                        		<td valign=top><BR>
	                        
	        		                <B>:: <? print $_lang[PayAuthorize]?></b><BR><BR>

						<? if ($authorize_comment) {print $authorize_comment."<BR><BR>";} ?>
	                        
	                        		<form method="POST" style="margin: 0;" action="billing.php">
       	        			        <input type=hidden name=do value="<? print $do?>">
		       		                <input type=hidden name=sub value="gotomerchant">
                	        		<input type=hidden name=id value="<? print $id?>">
                		        	<input type=hidden name=paytype value="authorize">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_authorize?> $">
        	                		</form>
						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "upc" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $upc_mid=$payy->text1;
	        		        $upc_tid=$payy->text2;
			                $upc_pem=$payy->text3;
	        		        $upc_crt=$payy->text4;
					$upc_currency=$payy->currency;
					$upc_comment=$payy->comment;
			                if ($upc_mid and $upc_tid and $upc_pem and $upc_crt and file_exists($upc_pem) and file_exists($upc_crt)) {
						if ($upc_currency) {
							$upc_currency = GetCurrencyByCode($upc_currency);
							$money_upc = $money*$upc_currency->koeficient;
							$upc_symbol = $upc_currency->symbol;
						} else {
							$upc_currency = GetCurrencyByCode("UAH");
							$money_upc = $money*$upc_currency->koeficient;
							$upc_symbol = $upc_currency->symbol;
						}
						$money_upc = $money_upc + ($money_upc/100)*$payy->small1; $money_upc = round($money_upc,2);

			                        ?>
	                        		<tr>
	        		                <td valign=top><BR><A class=rootlink href="http://www.upc.ua/" target=_blank><img src="./_rootimages/logo_upc.gif" border=0></a></td>
			                        <td width=10>&nbsp;</td>
						<td width=1 bgcolor=#CCCCCC></td>
						<td width=10>&nbsp;</td>
	                        		<td><BR>
	                        
	        		                <B>:: <? print $_lang[payUPC]?></b><BR><BR>

						<? if ($upc_comment) {print $upc_comment."<BR><BR>";} ?>
	                        
						<form method="POST" style="margin: 0;" action="billing.php">
                			        <input type=hidden name=do value="<? print $do?>">
		        	                <input type=hidden name=sub value="gotomerchant">
	                        		<input type=hidden name=id value="<? print $id?>">
	        		                <input type=hidden name=paytype value="upc">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
						<input class=button type=submit value='<? print $_lang[Pay]?> <? print $money_upc?> <? print $upc_symbol?>'>
						</form>

						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "a1lite" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
	        		        $a1lite_key=$payy->text1;
	        		        $a1lite_secret=decodePwd($payy->pass1);
					$a1lite_comment=$payy->comment;
	        		        if ($a1lite_key and $a1lite_secret) {
						$money_a1lite = $money_rub + ($money_rub/100)*$payy->small1; $money_a1lite = round($money_a1lite,2);
	                        		?>
							<tr>
							<td valign=top><BR><A class=rootlink href="https://www.a1pay.ru/" target=_blank><img src="./_rootimages/logo_a1pay.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
       			        		        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payA1Lite]?></B><BR><BR>

							<? if ($a1lite_comment) {print $a1lite_comment."<BR><BR>";} ?>
						
		                		        <form method="POST" action="billing.php">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="a1lite">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
				                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_a1lite?> <? print $_lang[PaySokraschenieRubl]?>">
		                		        </form>

							<Br>
                        
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "kvitancii" and !$workWithoutAuth and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
					$kvs = GetKvitancii(1);
			                if (@mysql_num_rows($kvs) > 0) {
						while ($kv = @mysql_fetch_object($kvs)) {
							if (!($kv->noShowForOrg and ($profile->org == "2" or $profile->org == "3"))) {
					                        ?>
					                        <tr>
								<td valign=top><BR>&nbsp;&nbsp;&nbsp;&nbsp;<b style="font-size: 30">>>></b></td>
	        			        	        <td width=10>&nbsp;</td>
								<td width=1 bgcolor=#CCCCCC></td>
								<td width=10>&nbsp;</td>
	        			        	        <td><BR>
	                        
				                        	<B>:: <? print $_lang[payKvitanciya]?></b><BR><BR>

								<? print preg_replace("/\r\n/ui", "<BR>", htmlDecode($kv->comment))?><br><br>

					                        <input class=button type=button value="<? print $_lang[PayGetKvitansiya]?> (HTML)" onClick="popupWin = window.open('billing.php?do=<? print $do?>&id=<? print $id?>&sub=kvitanciya&kvid=<? print $kv->id?>', 'kvitanciya', 'location=no,width=650,height=600,top=0'); popupWin.focus(); return false;"><BR>
								<form style="margin: 0;" method=post><input type=hidden name=do value=<? print $do?>><input type=hidden name=id value=<? print $id?>><input type=hidden name=sub value=kvitanciyapdf><input type=hidden name=kvid value=<? print $kv->id?>><input type=hidden name=paymentId value=<? print $payy->id?>><input class=button type=submit value="<? print $_lang[PayGetKvitansiya]?> (PDF)"></form><BR>

								<BR>
	                        
	        			        	        </td></tr>
			                	        	<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
		                			        <?
							}
						}
			                }
				}
				else if ($payy->type == "scheta" and !$workWithoutAuth and !$r->isMainAttach and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
					$fs = GetFakturas(1);
			                if (@mysql_num_rows($fs) > 0) {
						while ($f = @mysql_fetch_object($fs)) {
							if (!($f->noShowForPerson and $profile->org == "1")) {
		        			                ?>
				                	        <tr>
								<td valign=top><BR>&nbsp;&nbsp;&nbsp;&nbsp;<b style="font-size: 30">>>></b></td>
	        			        	        <td width=10>&nbsp;</td>
								<td width=1 bgcolor=#CCCCCC></td>
								<td width=10>&nbsp;</td>
        				        	        <td><BR>
                        
				                        	<B>:: <? print $_lang[paySchet]?></b><BR><BR>

								<? print preg_replace("/\r\n/ui", "<BR>", htmlDecode($f->comment))?><br><br>
			
	        				                <input class=button type=button value="<? print $_lang[PayGetSchetFaktura]?> (HTML)" onClick="popupWin = window.open('billing.php?do=<? print $do?>&id=<? print $id?>&sub=faktura&fid=<? print $f->id?>', 'faktura', 'location=no,width=650,height=700,top=0'); popupWin.focus(); return false;"><BR>
				        	                <form style="margin: 0;" method=post><input type=hidden name=do value=<? print $do?>><input type=hidden name=id value=<? print $id?>><input type=hidden name=sub value=fakturapdf><input type=hidden name=fid value=<? print $f->id?>><input type=hidden name=paymentId value=<? print $payy->id?>><input class=button type=submit value="<? print $_lang[PayGetSchetFaktura]?> (PDF)"></form><BR>
			
								<BR>
	                        
	        		        		        </td></tr>
			                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
		                		        	<?
							}
						}
					}
				}
				else if ($payy->type == "dop" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
					$l = GetPayMethods();
					while ($ll = @mysql_fetch_object($l)) {
						if ($ll->active) {
							if ($ll->logo_url) { $logo_url = "<img src=\"$ll->logo_url\" border=0>"; } else { $logo_url = "&nbsp;&nbsp;&nbsp;&nbsp;<b style=\"font-size: 30\">>>></b>";}
						?>
							<tr>
							<td valign=top><BR><? print $logo_url?></td>
        		        		        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $ll->name?></B><BR><BR>

							<?
							$sid=sprintf("%04d", $id);

						     	$ll->comment = preg_replace('/{money_usd}/ui',round($money_usd,2),$ll->comment);
						     	$ll->comment = preg_replace('/{money_rub}/ui',round($money_rub,2),$ll->comment);
						     	$ll->comment = preg_replace('/{money_uah}/ui',round($money_uah,2),$ll->comment);
						     	$ll->comment = preg_replace('/{money_eur}/ui',round($money_eur,2),$ll->comment);
						     	$ll->comment = preg_replace('/{money_byr}/ui',round($money_byr,2),$ll->comment);
						     	$ll->comment = preg_replace('/{schet}/ui',$sid,$ll->comment);
							?>

							<? print preg_replace("/\r\n/ui", "<BR>", htmlDecode($ll->comment))?><br><br>

		                		        </td></tr>
		                	        	<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
						<?
						}
					}
				}
				else if ($payy->type == "taulinkkz" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
					$userBalanceEnable = GetSetting("userBalanceEnable");
					$taulinkkz_comment = $payy->comment;
	        		        if ($userBalanceEnable) {
	                        		?>
							<tr>
							<td valign=top><BR><A class=rootlink href="https://www.taulink.kz/" target=_blank><img src="./_rootimages/logo_taulinkkz.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
       			        		        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payTaulinkKZ]?></B><BR><BR>

							<? print $_lang[payTaulinkKZDetails]." ".$user->id?><BR><BR>

							<? if ($taulinkkz_comment) {print $taulinkkz_comment."<BR><BR>";} ?>
						
							<Br>
                        
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "qiwikz" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
					$userBalanceEnable = GetSetting("userBalanceEnable");
					$qiwikz_comment = $payy->comment;
	        		        if ($userBalanceEnable) {
	                        		?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://www.qiwi.kz/" target=_blank><img src="./_rootimages/logo_qiwikz.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
       			        		        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payQiwiKZ]?></B><BR><BR>

							<? print $_lang[payQiwiKZDetails]." ".$user->id?><BR><BR>

							<? if ($qiwikz_comment) {print $qiwikz_comment."<BR><BR>";} ?>
						
							<Br>
                        
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "easysoft" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
					$userBalanceEnable = GetSetting("userBalanceEnable");
					$easysoft_comment = $payy->comment;
	        		        if ($userBalanceEnable) {
	                        		?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://easypay.ua/" target=_blank><img src="./_rootimages/logo_easysoft.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
       			        		        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payEasySoft]?></B><BR><BR>

							<? print $_lang[payEasySoftDetails]." ".sprintf("%04d", $user->id);?><BR><BR>

							<? if ($easysoft_comment) {print $easysoft_comment."<BR><BR>";} ?>
						
							<Br>
                        
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "telemoney" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $telemoney_id=$payy->text1;
	        		        $telemoney_secret=decodePwd($payy->pass1);
					$telemoney_comment=$payy->comment;
			                if ($telemoney_id) {
						$money_telemoney = $money_rub + ($money_rub/100)*$payy->small1; $money_telemoney = round($money_telemoney,2);
	        		                ?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://telemoney.ru/" target=_blank><img src="./_rootimages/logo_telemoney.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				                        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payTeleMoney]?></b><BR><BR>

							<? if ($telemoney_comment) {print $telemoney_comment."<BR><BR>";} ?>
						
							<B><? print $_lang[PayBill]?>:</B> <? print $telemoney_id?> (<? print $_lang[PayToPay]?> <? print $money_telemoney?> <? print $_lang[PaySokraschenieRubl]?>)<BR><BR>
						
							<? if ($telemoney_id and $telemoney_secret) { ?>
							<form method="POST" action="billing.php">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="telemoney">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
							<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_telemoney?> <? print $_lang[PaySokraschenieRubl]?>">
							</form><BR>
				                        <? } ?>
                        
     
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "intellectmoney" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $intellectmoney_id=$payy->text1;
	        		        $intellectmoney_secret=decodePwd($payy->pass1);
					$intellectmoney_comment=$payy->comment;
			                if ($intellectmoney_id) {
						$money_intellectmoney = $money_rub + ($money_rub/100)*$payy->small1; $money_intellectmoney = round($money_intellectmoney,2);
	        		                ?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://intellectmoney.ru/" target=_blank><img src="./_rootimages/logo_intellectmoney.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				                        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payIntellectMoney]?></b><BR><BR>

							<? if ($intellectmoney_comment) {print $intellectmoney_comment."<BR><BR>";} ?>
						
							<B><? print $_lang[PayBill]?>:</B> <? print $intellectmoney_id?> (<? print $_lang[PayToPay]?> <? print $money_intellectmoney?> <? print $_lang[PaySokraschenieRubl]?>)<BR><BR>
						
							<? if ($intellectmoney_id and $intellectmoney_secret) { ?>
							<form action="billing.php" method="POST">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="intellectmoney">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
							<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_intellectmoney?> <? print $_lang[PaySokraschenieRubl]?>">
							</form><BR>
				                        <? } ?>
                        
     
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "monexy" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $monexy_id=$payy->text1;
	        		        $monexy_secret=decodePwd($payy->pass1);
	        		        $monexy_currency=$payy->currency;
					$monexy_comment=$payy->comment;
			                if ($monexy_id and $monexy_secret) {
						if (!$monexy_currency) {$monexy_currency = "UAH";}

						$monexy_currency = GetCurrencyByCode($monexy_currency);
						$money_monexy = $money*$monexy_currency->koeficient;
						$monexy_symbol = $monexy_currency->symbol;

						$money_monexy = $money_monexy + ($money_monexy/100)*$payy->small1; $money_monexy = round($money_monexy,2);
	        		                ?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://monexy.com/" target=_blank><img src="./_rootimages/logo_monexy.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				                        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payMoneXy]?></b><BR><BR>

							<? if ($monexy_comment) {print $monexy_comment."<BR><BR>";} ?>
						
							<form action="billing.php" method="POST">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="monexy">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
							<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_monexy?> <? print $monexy_symbol?>">
							</form><BR>
     
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "netcard" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $netcard_id=$payy->text1;
					$netcard_comment=$payy->comment;
			                if ($netcard_id) {
						$netcard_currency = "AZN";

						$netcard_currency = GetCurrencyByCode($netcard_currency);
						$money_netcard = $money*$netcard_currency->koeficient;
						$netcard_symbol = $netcard_currency->symbol;

						$money_netcard = round($money_netcard,2);
	        		                ?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://netcard.az/" target=_blank><img src="./_rootimages/logo_netcard.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				                        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payNetCard]?></b><BR><BR>

							<? if ($netcard_comment) { print $netcard_comment."<BR><BR>";} ?>
						
							<form action="billing.php" method="POST">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="netcard">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
							<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_netcard?> <? print $netcard_symbol?>">
							</form><BR>
     
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "netmoney" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $netmoney_id=$payy->text1;
					$netmoney_comment=$payy->comment;
			                if ($netmoney_id) {
						$netmoney_currency = "AZN";

						$netmoney_currency = GetCurrencyByCode($netmoney_currency);
						$money_netmoney = $money*$netmoney_currency->koeficient;
						$netmoney_symbol = $netmoney_currency->symbol;

						$money_netmoney = round($money_netmoney,2);
	        		                ?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://netmoney.az/" target=_blank><img src="./_rootimages/logo_netmoney.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				                        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payNetMoney]?></b><BR><BR>

							<? if ($netmoney_comment) { print $netmoney_comment."<BR><BR>";} ?>
						
							<form action="billing.php" method="POST">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="netmoney">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
							<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_netmoney?> <? print $netmoney_symbol?>">
							</form><BR>
     
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "assist" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $assist_shopid=$payy->text1;
					$assist_comment=$payy->comment;
			                if ($assist_shopid) {
						$money_assist = $money_rub + ($money_rub/100)*$payy->small1; $money_assist = round($money_assist,2);
	        		                ?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://assist.ru/" target=_blank><img src="./_rootimages/logo_assist.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				                        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payAssist]?></b><BR><BR>

							<? if ($assist_comment) {print $assist_comment."<BR><BR>";} ?>
						
							<form action="billing.php" method="POST">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="assist">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
							<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_assist?> <? print $_lang[PaySokraschenieRubl]?>">
							</form><BR>
     
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "kkbkz" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
					$kkbkz_merchant_certificate_id=$payy->text4;
					$kkbkz_merchant_id=$payy->text2;
					$kkbkz_merchant_name=$payy->text1;
					$kkbkz_private_key=$payy->text6;
					$kkbkz_private_key_pass=decodePwd($payy->pass1);
					$kkbkz_public_key=$payy->text5;
					$kkbkz_comment=$payy->comment;

			                if ($kkbkz_merchant_certificate_id and $kkbkz_merchant_id and $kkbkz_merchant_name and $kkbkz_private_key and $kkbkz_private_key_pass and $kkbkz_public_key and file_exists($kkbkz_private_key) and file_exists($kkbkz_public_key)) {
						$kkbkz_currency = GetCurrencyByCode("KZT");
						$kkbkz_symbol = $kkbkz_currency->symbol;

						$money_kkbkz = $money_kzt + ($money_kzt/100)*$payy->small1; $money_kkbkz = round($money_kkbkz);
	        		                ?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://kkb.kz/" target=_blank><img src="./_rootimages/logo_kkbkz.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				                        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payKkbkz]?></b><BR><BR>

							<? if ($kkbkz_comment) {print $kkbkz_comment."<BR><BR>";} ?>
						
							<form action="billing.php" method="POST">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="kkbkz">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
							<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_kkbkz?> <? print $kkbkz_symbol?>">
							</form><BR>
     
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "kzmkz" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
					$kzmkz_merchant_id=$payy->text1;
					$kzmkz_public_key=$payy->text2;
					$kzmkz_private_key=$payy->text3;
					$kzmkz_private_key_pass=decodePwd($payy->pass1);
					$kzmkz_comment=$payy->comment;

			                if ($kzmkz_merchant_id and $kzmkz_public_key and $kzmkz_private_key and $kzmkz_private_key_pass and file_exists($kzmkz_private_key) and file_exists($kzmkz_public_key)) {
						$kzmkz_currency = GetCurrencyByCode("KZT");
						$kzmkz_symbol = $kzmkz_currency->symbol;

						$money_kzmkz = $money_kzt + ($money_kzt/100)*$payy->small1; $money_kzmkz = round($money_kzmkz);
	        		                ?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://kzm.kz/" target=_blank><img src="./_rootimages/logo_kzmkz.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				                        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payKzmkz]?></b><BR><BR>

							<? if ($kzmkz_comment) {print $kzmkz_comment."<BR><BR>";} ?>
						
							<form action="billing.php" method="POST">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="kzmkz">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
							<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_kzmkz?> <? print $kzmkz_symbol?>">
							</form><BR>
     
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "copayco" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $copayco_shop_id=$payy->text1;
	        		        $copayco_secret=decodePwd($payy->pass1);
			                $copayco_currency=$payy->currency;
					$copayco_comment=$payy->comment;
			                if ($copayco_shop_id and $copayco_secret) {
						if (!$copayco_currency) {$copayco_currency = "UAH";}

						$copayco_currency = GetCurrencyByCode($copayco_currency);
						$money_copayco = $money*$copayco_currency->koeficient;
						$copayco_symbol = $copayco_currency->symbol;

						$money_copayco = $money_copayco + ($money_copayco/100)*$payy->small1; $money_copayco = round($money_copayco,2);

			                        ?>
	                	        	<tr>
	        	        	        <td valign=top><BR><A class=rootlink href="http://www.copayco.com/" target=_blank><img src="./_rootimages/logo_copayco.gif"  border=0></a></td>
			                        <td width=10>&nbsp;</td>
						<td width=1 bgcolor=#CCCCCC></td>
						<td width=10>&nbsp;</td>
			                        <td><BR>
	                        
	                		        <B>:: <? print $_lang[PayCoPAYCo]?></b><BR><BR>

						<? if ($copayco_comment) {print $copayco_comment."<BR><BR>";} ?>
                        
		                        	<form method="POST" style="margin: 0;" action="billing.php">
       	        			        <input type=hidden name=do value="<? print $do?>">
		       		                <input type=hidden name=sub value="gotomerchant">
                	        		<input type=hidden name=id value="<? print $id?>">
                		        	<input type=hidden name=paytype value="copayco">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
			                        <input class=button type=submit value="<? print $_lang[Pay]?> <? print $money_copayco?> <? print $copayco_symbol?>">
        	                		</form>
						<BR>
	                        
			                        </td></tr>
	                        		<tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "qiwi" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $qiwi_id=$payy->text1;
	        		        $qiwi_secret=decodePwd($payy->pass1);
					$qiwi_comment=$payy->comment;
			                if ($qiwi_id and $qiwi_secret) {
						$money_qiwi = $money_rub + ($money_rub/100)*$payy->small1; $money_qiwi = round($money_qiwi,2);
	        		                ?>
						<tr>
						<td valign=top><BR><A class=rootlink href="http://w.qiwi..ru/" target=_blank><img src="./_rootimages/logo_qiwi.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
			                        <td width=10>&nbsp;</td>
						<td width=1 bgcolor=#CCCCCC></td>
						<td width=10>&nbsp;</td>
						<td><BR>
						
						<B>:: <? print $_lang[payQIWI]?></b><BR><BR>

						<? if ($qiwi_comment) {print $qiwi_comment."<BR><BR>";} ?>
						
						<form action="billing.php" method="POST">
        		        	        <input type=hidden name=do value="<? print $do?>">
		        	                <input type=hidden name=sub value="gotomerchant">
	                		        <input type=hidden name=id value="<? print $id?>">
			                        <input type=hidden name=paytype value="qiwi">
						<input type=hidden name=paymentId value=<? print $payy->id?>>
						<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_qiwi?> <? print $_lang[PaySokraschenieRubl]?>">
						</form><BR>
     
			                        </td></tr>
	                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "sprypay" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $sprypay_id=$payy->text1;
	        		        $sprypay_secret=decodePwd($payy->pass1);
	        		        $sprypay_currency=$payy->currency;
					$sprypay_comment=$payy->comment;
			                if ($sprypay_id and $sprypay_secret) {
						if (!$sprypay_currency) {$sprypay_currency = "USD";}

						$sprypay_currency = GetCurrencyByCode($sprypay_currency);
						$money_sprypay = $money*$sprypay_currency->koeficient;
						$sprypay_symbol = $sprypay_currency->symbol;

						$money_sprypay = $money_sprypay + ($money_sprypay/100)*$payy->small1; $money_sprypay = round($money_sprypay,2);
	        		                ?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://sprypay.ru/" target=_blank><img src="./_rootimages/logo_sprypay.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				                        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[paySpryPay]?></b><BR><BR>

							<? if ($sprypay_comment) {print $sprypay_comment."<BR><BR>";} ?>
						
							<form action="billing.php" method="POST">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="sprypay">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
							<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_sprypay?> <? print $sprypay_symbol?>">
							</form><BR>
     
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "liberty" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $liberty_id=$payy->text1;
			                $liberty_store=$payy->text2;
	        		        $liberty_secret=decodePwd($payy->pass1);
	        		        $liberty_currency=$payy->currency;
					$liberty_comment=$payy->comment;
			                if ($liberty_id and $liberty_store and $liberty_secret) {
						if (!$liberty_currency) {$liberty_currency = "USD";}

						$liberty_currency = GetCurrencyByCode($liberty_currency);
						$money_liberty = $money*$liberty_currency->koeficient;
						$liberty_symbol = $liberty_currency->symbol;

						$money_liberty = $money_liberty + ($money_liberty/100)*$payy->small1; $money_liberty = round($money_liberty,2);
	        		                ?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://libertyreserver.com/" target=_blank><img src="./_rootimages/logo_liberty.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				                        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payLibertyReserve]?></b><BR><BR>

							<? if ($liberty_comment) {print $liberty_comment."<BR><BR>";} ?>
						
							<form action="billing.php" method="POST">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="liberty">
							<input type=hidden name=paymentId value=<? print $payy->id?>>
							<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_liberty?> <? print $liberty_symbol?>">
							</form><BR>
     
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
				else if ($payy->type == "onpay" and (($payy->isdefault and !@in_array($payy->id,$disallowedPayments)) or @in_array($payy->id,$allowedPayments))) {
			                $onpay_login=$payy->text1;
	        		        $onpay_secret=decodePwd($payy->pass1);
					$onpay_select1=$payy->select1; $onpay_select1 = @mb_split(":x:", $onpay_select1);
					$onpay_currency=$payy->currency;
					$onpay_comment=$payy->comment;
			                if ($onpay_login and $onpay_secret) {
						if (!$onpay_currency) {$onpay_currency = "RUB";}

						$onpay_currency = GetCurrencyByCode($onpay_currency);
						$money_onpay = $money*$onpay_currency->koeficient;
						$onpay_symbol = $onpay_currency->symbol;

						$money_onpay = $money_onpay + ($money_onpay/100)*$payy->small1; $money_onpay = round($money_onpay,2);
	        		                ?>
							<tr>
							<td valign=top><BR><A class=rootlink href="http://onpay.ru/" target=_blank><img src="./_rootimages/logo_onpay.gif" border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				                        <td width=10>&nbsp;</td>
							<td width=1 bgcolor=#CCCCCC></td>
							<td width=10>&nbsp;</td>
							<td><BR>
						
							<B>:: <? print $_lang[payOnPay]?></b><BR><BR>

							<? if ($onpay_comment) {print $onpay_comment."<BR><BR>";} ?>
						
							<form action="billing.php" method="POST">
	        		        	        <input type=hidden name=do value="<? print $do?>">
			        	                <input type=hidden name=sub value="gotomerchant">
		                		        <input type=hidden name=id value="<? print $id?>">
				                        <input type=hidden name=paytype value="onpay">
							<input type=hidden name=paymentId value=<? print $payy->id?>>

							<? print $_lang[PayOnpaySelect]?>:</b><BR>
							<select name="payid" class="input">
							<? 
							while (list($sid,$sname) = @each($_pays[onpay][select1checks])) {
								if (@in_array($sid,$onpay_select1)) { print "<option value=\"$sid\">$sname</option>"; }
							}
							?>
							</select><BR><BR>

							<input class=button type="submit" name="button" value="<? print $_lang[Pay]?> <? print $money_onpay?> <? print $onpay_symbol?>">
							</form><BR>
     
				                        </td></tr>
		                		        <tr bgcolor=#CCCCCC><td colspan=5 height=1></td></tr>
	        		                <?
			                }
				}
			}

	                print "</table><BR><BR>";
                	}
		}
                foot('utf-8');
        }
}

if ($do == "renew") {
	$hostMonths=@intval($hostMonths);
	$host_id=@intval($host_id);
	$domainMonths=@intval($domainMonths);

	$allowCancelAddonsRenew = GetSetting("allowCancelAddonsRenew");

	if ($sub == 'renew') {
		if (!$hostMonths or $hostMonths <= 0) {$error=$_lang[RenewErrorNoSrok];}
		else {
			$order=@mysql_query("select * from orders where archived=0 and id='$host_id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			if (mysql_num_rows($order) == 0) {$error=$_lang[ErrorBadId];}
			else {
				if (!$promocode) {
					$currentUser = GetUserById($_SESSION["userId"]);
					if ($currentUser->specialPromoCode and $currentUser->specialPromoCodeForUser) {
						$promocode = $currentUser->specialPromoCode;
					}
					else if ($currentUser->referal) {
						$referalUser = GetUserById($currentUser->referal);
						if ($referalUser->specialPromoCode and $referalUser->specialPromoCodeForReferals) {
							$promocode = $referalUser->specialPromoCode;
						}
					}
				}

				$order=mysql_fetch_object($order);
				$orderAddons=mb_split(":x:", $order->addons);

				$addonsCost=0;
				$addonsToSave="";
				$addonsToSaveText="";
				$addonsToDelete=array();
				while (list($k,$v) = @each($orderAddons)) {
					if ($v) {
						$oneAddon = GetAddonById($v);
						if ($oneAddon->id) {
							$inArray = 0;
							$indexToDelete = "";
							reset($addonsToRenew);
							while (list($q,$w) = @each($addonsToRenew)) {
								if ($oneAddon->id == $w) { $inArray = 1; $indexToDelete = $q; break; }
							}
							if ($inArray) { unset($addonsToRenew[$indexToDelete]); } else { $addonsToDelete[] = $oneAddon->id; continue; }

							$addonSpecCost = GetSpecialCost($_SESSION['userId'],"addon",$oneAddon->id);

							if ($addonsToSaveText) {
								$addonsToSaveText = $addonsToSaveText.", ".$oneAddon->name;
							} else {
								$addonsToSaveText = $oneAddon->name;
							}

							if ($addonSpecCost) {
								$currCost = $addonSpecCost["cost2"]*$hostMonths;
							} else {
								$oneAddon->cost_monthly = $oneAddon->cost_monthly / GetCurrencyKoeficientByCode($oneAddon->cost_monthlyCurrency);
								$currCost = $oneAddon->cost_monthly*$hostMonths;
							}

							if ($promocode and $currCost > 0) {
								$coupon = GetCoupon("promo",$promocode,1,"addon",$oneAddon->id);
								if (IsCanUseCoupon($coupon->id,$_SESSION["userId"])) {
									$promoIds[] = $coupon->id;
									$promoSkidka = $coupon->value;
									$promoMaxSrok - $coupon->addonsMaxSrok;
									$promoTXT = ", promo-code $promocode";
									$promoCodeTXT = $promocode;

									$calcRes = calculatePromoDiscountWithMaxSrok($currCost, $hostMonths, $promoMaxSrok, $promoSkidka);

									$promoTotal += $calcRes[summDiscount];
									$currCost = $calcRes[totalSummWithDiscount];
								}
							}

							$addonsCost += $currCost;
						}
					}
				}
				if (!$addonsToSaveText) { $addonsToSaveText=$_yes[0]; }
				$addonsCost=round($addonsCost,2);
				$addonsToDelete = @join(":x:",$addonsToDelete);

				$tarif=@mysql_query("select * from tarifs where id='$order->tarif'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				$tarif=mysql_fetch_object($tarif);

				if ($order->costId) {
					$tc = GetTarifsCostById($order->costId);
					$hostCost = $tc->cost / GetCurrencyKoeficientByCode($tc->costCurrency);
					$hostCost = $hostCost * $hostMonths;
				} else {
					$tarifSpecCost = GetSpecialCost($_SESSION['userId'],"tarif",$tarif->id);
					if ($tarifSpecCost) {
						$hostCost=$tarifSpecCost["cost2"]*$hostMonths;
					} else {
						$tarif->cost = $tarif->cost / GetCurrencyKoeficientByCode($tarif->costCurrency);
						$hostCost=$tarif->cost*$hostMonths;
					}
				}

				if ($tarif->enableSlots and $order->slots > 0) {
					$hostCost = $hostCost*$order->slots;
				}

				if ($order->serverid) {
					$srv = GetServers($order->serverid);
					if ($srv->nacenka) { $srvNacenka = $srv->nacenka; }
				}
				if ($srvNacenka) {
					$hostCost=$hostCost+($hostCost/100)*$srvNacenka;
				}

				$tsroki=@mysql_query("select discount from tarifs_sroki where tarif_id='$order->tarif' and months='$hostMonths' and renew='1'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				$tsroki=mysql_fetch_object($tsroki);
				$srokDiscount=$tsroki->discount;
				$orderDiscount=$order->discount;
				$host=$hostCost-($hostCost/100)*($srokDiscount+$orderDiscount);
				if ($orderDiscount == '100') {$host = 0;}

				if ($promocode and $host > 0) {
					$coupon = GetCoupon("promo",$promocode,1,"tarif",$order->tarif);
					if (IsCanUseCoupon($coupon->id,$_SESSION["userId"])) {
						$promoIds[] = $coupon->id;
						$promoSkidka = $coupon->value;
						$promoMaxSrok = $coupon->tarifsMaxSrok;
						$promoTXT = ", promo-code $promocode";
						$promoCodeTXT = $promocode;

						$calcRes = calculatePromoDiscountWithMaxSrok($host, $hostMonths, $promoMaxSrok, $promoSkidka);

						$promoTotal += $calcRes[summDiscount];
						$host = $calcRes[totalSummWithDiscount];
					}
				}
				$host=round($host,2);

				$domainCost=0;
				$domain_renew=0;
				if (($order->domain_reg == "1" or $order->domain_reg == "3") and $domainMonths > 0) {
					$domain_renew=1;

					$order_domain=GetDomainByDomain($order->domain);
					$zone=GetZoneById($order_domain->zone_id);

					$domainCost = GetDomainCostRenewForUserByZoneId($_SESSION["userId"],$zone->id,1);

					$domainCost=$domainCost * ($domainMonths/12);
					$domainDiscount = $order_domain->discount;
					$domainCost = $domainCost - ($domainCost/100)*$domainDiscount;
					if ($domainDiscount == '100') {$domainCost = 0;}

					if ($order_domain->privacy) {
						$privacyCost = $zone->privacy_cost;
					} else {
						$privacyCost = 0;
					}

					if ($order_domain->localContact) {
						$localContactCost = $zone->localContact_cost;
					} else {
						$localContactCost = 0;
					}

					$allsumm = $host+$domainCost+$addonsCost+$privacyCost+$localContactCost;
					$domainFree = 0;

					$tfreedomains=@mysql_query("select * from tarifs_freedomains where tarif_id='$order->tarif' and zone='$zone->zone' and hostmonths='$hostMonths' and renew='1'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
					if (@mysql_num_rows($tfreedomains) > 0) {$domainFree=1;}

					$tfreedomains=@mysql_query("select * from tarifs_freedomains where tarif_id='$order->tarif' and zone='$zone->zone' and ordersum > 0 and ordersum <= $allsumm and renew='1'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
					if (@mysql_num_rows($tfreedomains) > 0) {$domainFree=1;}

					if ($domainFree) {
						$domainCost = 0; 
						$bonus = $_lang[OrderFreeDomainInZone]." .$zone->zone";
					}

					if ($promocode and $domainCost > 0) {
						$coupon = GetCoupon("promo",$promocode,1,"zone",$zone->id);
						if (IsCanUseCoupon($coupon->id,$_SESSION["userId"])) {
							$promoIds[] = $coupon->id;
							$promoSkidka = $coupon->value;
							$promoMaxSrok = $coupon->zonesMaxSrok;
							$promoTXT = ", promo-code $promocode";
							$promoCodeTXT = $promocode;

							$calcRes = calculatePromoDiscountWithMaxSrok($domainCost, $domainMonths, $promoMaxSrok, $promoSkidka);

							$promoTotal += $calcRes[summDiscount];
							$domainCost = $calcRes[totalSummWithDiscount];
						}
					}

				} else {
					$domainMonths = 0;
				}

				if ($order->domain_reg == "1" or $order->domain_reg == "3") {
					$newreg=$_renew[$domain_renew];
				} else {
					$newreg='-';
				}
				$domainCost=round($domainCost+$privacyCost+$localContactCost,2);

				$history = "Тариф: <B>$tarif->name</B>, $hostMonths мес.";
				if ($order_domain->id) { $history .= " + домен <B>$order->domain</B>, ".($domainMonths/12)." г."; }
				if ($order_domain->privacy) { $history .= " + <B>".$_lang[OrderPrivacy]."</B>"; }
				if ($order_domain->localContact) { $history .= " + <B>".$_lang[OrderLocalContact]."</B>"; }
				if ($addonsToSaveText and $addonsToSaveText != $_yes[0]) { $history .= " + $addonsToSaveText"; }

				@mysql_query("insert into bills (uid,tarif,host_id,domain_id,host_srok,domain_srok,money_host,money_domain,money_addons,created,renew,history,deleteaddons,promocode) values('".$_SESSION['userId']."','$tarif->id','$order->id','$order_domain->id','$hostMonths','$domainMonths','$host','$domainCost','$addonsCost',NOW(),'1','$history','$addonsToDelete','$promoCodeTXT')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				$bill_id=mysql_insert_id();
				$sid=sprintf("%04d", $bill_id);

				$tpl=GetTpl("email_touser_".$tarif->vid."_renew", $_SESSION["userLang"]);
				$subject = $tpl[subject]; $template = $tpl[template];

			     	addUserLog($_SESSION['userId'], "renew", "$tarif->name, $hostMonths ".$_lang[OrderSokraschenieMonth].", $order->domain [$newreg (".($domainMonths/12)." ".$_lang[OrderSokraschenieGod].")]$promoTXT");

				$promoOk = array();
				while (list($k,$pr) = @each($promoIds)) {
					if (!@in_array("$pr",$promoOk)) {
						AddUsedByToCoupon($pr,$_SESSION["userId"],$bill_id);
						$promoOk[] = $pr;
					}
				}

				if ($subject and $template) {
					$attachPDFtoBill = GetSetting("attachPDFtoBill");
					if (($attachPDFtoBill and $_SESSION["userAttachPDFtoBill"] != "2") or (!$attachPDFtoBill and $_SESSION["userAttachPDFtoBill"] == "1")) {
						$profile=GetUserProfileByUserId($_SESSION['userId']);

						if ($profile->org == "3" and $profile->firma and $profile->phone) {
							$attachFile = createFaktura('', $bill_id, 2);
						} else if ($profile->org == "2" and $profile->name and $profile->surname and $profile->phone) {
							$attachFile = createFaktura('', $bill_id, 2);
						} else if ($profile->org == "1") {
							$attachFile = createKvitanciya('', $bill_id, 2);

							if ($profile->name and $profile->surname and $profile->phone) {
								$attachFile2 = createFaktura('', $bill_id, 2);
							}
						}
						if (!$attachFile) {$attachFile="";}
						if (!$attachFil2) {$attachFile2="";}
					}

					$company_name=GetSetting('company_name');
					$company_url=GetSetting('company_url');
					$billing_url=GetSetting('billing_url');
					$support_url=GetSetting('support_url');
					$manager_email=GetSetting('manager_email');

					if ($order_domain->privacy) { $privacyAddon = " + ".$_lang[OrderPrivacy]; }
					if ($order_domain->localContact) { $localContactAddon = " + ".$_lang[OrderLocalContact]; }

					$template = str_replace('{company_name}',$company_name,$template);
				     	$template = str_replace('{company_url}',$company_url,$template);
				     	$template = str_replace('{billing_url}',$billing_url,$template);
				     	$template = str_replace('{support_url}',$support_url,$template);
				     	$template = str_replace('{tarif}',$tarif->name,$template);
				     	$template = str_replace('{srok}',$hostMonths,$template);
				     	$template = str_replace('{domain}',$order->domain.$privacyAddon.$localContactAddon,$template);
				     	$template = str_replace('{addons}',$addonsToSaveText,$template);
				     	$template = str_replace('{bonus}',$bonus,$template);
				     	$template = str_replace('{newreg}',$newreg,$template);
				     	$template = str_replace('{login}',$_SESSION["userLogin"],$template);
				     	$template = str_replace('{password}',"******",$template);
				     	$template = str_replace('{schet}',$sid,$template);
				     	$template = str_replace('{hostcost}',round($host*CURK,2)." ".CURS,$template);
				     	$template = str_replace('{domaincost}',round($domainCost*CURK,2)." ".CURS,$template);
				     	$template = str_replace('{addonscost}',round($addonsCost*CURK,2)." ".CURS,$template);
				     	$template = str_replace('{cost}',round(($host+$domainCost+$addonsCost)*CURK,2)." ".CURS,$template);
				     	$template = str_replace('{userid}',$_SESSION['userId'],$template);
				     	$template = str_replace('{slots}',$order->slots,$template);
				     	$template = str_replace('{promocode}',$promoCodeTXT,$template);

					WriteMailLog($subject,$template,$_SESSION["userId"]);
					sendmail($_SESSION["userEmail"],$company_name,$manager_email,$subject,$template,$attachFile,$attachFile2,$tpl[type]);
					sendmail($_SESSION["userEmail2"],$company_name,$manager_email,$subject,$template,$attachFile,$attachFile2,$tpl[type]);

					$admEmails=GetAdminEmailsWhereTrueParam("sendneworder");
					if (count($admEmails) > 0) {
						WriteMailLog("Duplicate: ".$subject,$template);
					}
					while (list($i,$em) = @each($admEmails)) {
						sendmail($em,'',$manager_email,"Duplicate: ".$subject,$template,$attachFile,$attachFile2,$tpl[type]);
					}

					@unlink($attachFile);
					@unlink($attachFile2);
				}

				Header("Location: billing.php?do=pay&fromreg=1&id=$bill_id");

				mclose();
				exit;
			}
		}
	}

	$bnp=mysql_query("select * from bills where archived=0 and host_id = '$host_id' and uid='".$_SESSION["userId"]."' and status='0'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
	if (mysql_num_rows($bnp) > 0) {$error = $_lang[ErrorBadId]; $isCriticalError = 1;}

	$r=@mysql_query("select * from orders where archived=0 and id='$host_id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
	if (mysql_num_rows($r) == 0) {$error = $_lang[ErrorBadId];}

	head('utf-8',$_lang[RenewTitle]);
	print "<H1 class=pagetitle>".$_lang[RenewTitle]."</H1><hr class=hr>";
	if ($error) {print $_lang[Error].": $error<BR><BR>";}

	if (mysql_num_rows($r) > 0 and !$isCriticalError) {
		$r=mysql_fetch_object($r);
		$tarif=GetTarifById($r->tarif);
		$tarif_sroki=@mysql_query("select * from tarifs_sroki where tarif_id='$tarif->id' and renew='1' order by months") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

		?>
		<form method=post>
		<input type=hidden name=do value=<? print $do?>>
		<input type=hidden name=sub value=renew>
		<input type=hidden name=host_id value=<? print $host_id?>>

		<table class='rpTableBlank' border=0>
		<? if ($r->domain) {?><tr><td align=right><? print $_lang[RenewDomain]?>:&nbsp;</td><td><input class=input type=text name=domain value="<? print $r->domain?>" readonly></td></tr><? } ?>
		<?
		if ($r->domain_reg == "1" or $r->domain_reg == "3") {
			$domain = GetDomainByDomain($r->domain);
			$zone = GetZoneById($domain->zone_id);

			if (!$zone->daysRenew or ($zone->daysRenew and $zone->daysRenew >= $domain->leftdays)) {
				$minsrok=$zone->minsrok_renew;
				?>
				<tr><td align=right><? print $_lang[RenewNaSrok]?>:<BR><BR></td><Td><select class=input name=domainMonths><option value=0><? print $_lang[DomainWithoutRenewMin]?></option><option value=<? print $minsrok?>><? print ($minsrok/12)?> <? print $_lang[RenewYear]?></option>
				</select><BR><BR></td></tr>
				<?
			}
		}

		if ($r->costId) {
			$tc = GetTarifsCostById($r->costId);
			$tarif->cost = $tc->cost / GetCurrencyKoeficientByCode($tc->costCurrency);
		} else {
			$tarifSpecCost = GetSpecialCost($_SESSION['userId'],"tarif",$tarif->id);
			if ($tarifSpecCost) {
				$tarif->cost=$tarifSpecCost["cost2"];
			} else {
				$tarif->cost = $tarif->cost / GetCurrencyKoeficientByCode($tarif->costCurrency);
			}
		}

		if ($tarif->enableSlots) {$slotsAddon = " ".$_lang[OrderForSlot];} else { $slotsAddon = ""; }

		?><tr><td align=right><? print $_lang[RenewTarif]?>:&nbsp;</td><td><input class=input type=radio name=tarif_id value=<? print $tarif->id?> checked><? print $tarif->name?> (<? print round($tarif->cost*CURK,2)?> <? print CURS?>/<? print $_lang[OrderSokraschenieMonth].$slotsAddon?>)</td></tr><?
		if ($tarif->enableSlots) { ?><tr><td align=right><? print $_lang[OrderSlotsCount]?>:&nbsp;</td><td><? print $r->slots?></td></tr><? }
		?><tr><td align=right><? print $_lang[RenewNaSrok]?>:&nbsp;</td><td><select class=input name=hostMonths><option></option><?

		if (mysql_num_rows($tarif_sroki) > 0) {
			while ($srok = @mysql_fetch_object($tarif_sroki)) {
				if ($srok->months == $hostMonths) {$selected='selected';} else {$selected='';}
				if ($srok->discount) {$skidka=" (".$_lang[OrderDiscountSmall]." $srok->discount%)";} else {$skidka="";}
				print "<option value=$srok->months $selected>$srok->months ".$_lang[OrderSokraschenieMonth].$skidka."</option>";
			}
		}
		?>
		</select></td></tr>
		<tr><td align=right valign=top><? print $_lang[RenewAddons]?>:&nbsp;</td><td>
		<?
                $orderAddons = mb_split(":x:", $r->addons);
                while (list($k,$v) = @each($orderAddons)) {
                        if ($v) {
                                $oneAddon=GetAddonById($v);
				if ($oneAddon->id) {
#				if ($oneAddon->cost_monthly > 0 or $oneAddon->isOs or $oneAddon->isPanel) {
					$addonSpecCost = GetSpecialCost($_SESSION['userId'],"addon",$oneAddon->id);
					if ($addonSpecCost) {
						$oneAddon->cost_monthly = $addonSpecCost["cost2"];
					} else {
						$oneAddon->cost_monthly = $oneAddon->cost_monthly / GetCurrencyKoeficientByCode($oneAddon->cost_monthlyCurrency);
					}

	                                if ($lastaddon) {$orderAddonsTxt .= "<BR>";}

					if ($allowCancelAddonsRenew and !$oneAddon->isOs and !$oneAddon->isPanel) { 
						$orderAddonsTxt .= "<input type=checkbox name=addonsToRenew[] value=$oneAddon->id checked>"; 
					} else {
						$orderAddonsTxt .= "<input type=hidden name=addonsToRenew[] value=$oneAddon->id>"; 
					}

	                                $orderAddonsTxt .= "$oneAddon->name (".round($oneAddon->cost_monthly*CURK,2)." ".CURS."/".$_lang[OrderSokraschenieMonth].")";
	                                $lastaddon=$oneAddon->textid;
#				}
				}
                        }
                }
		if (!$orderAddonsTxt) {$orderAddonsTxt=$_yes[0];}
		print $orderAddonsTxt;
		?>
		</td></tr>
		<? if (GetCouponActiveCount("promo") > 0) { ?>
		<tr><td align=right><? print $_lang[OrderPromoCode]?>:&nbsp;</td><td><input class=input type=text name=promocode value="<? print $promocode?>"></td></tr>
		<? } ?>
	
		<tr><td colspan=2 align=center><BR><input class=button type=submit value="<? print $_lang[RenewRenew]?>"></td></tr>
		</table>
		</form>
		<?
	}
	foot('utf-8');
}

if ($do == "renewdomain") {
	$domain_id=@intval($domain_id);
	$domainMonths=@intval($domainMonths);

	if ($sub == "renew") {
		if (!$domainMonths or $domainMonths <= 0) {$error=$_lang[RenewErrorNoSrok];}
		else {
			$r=@mysql_query("select * from orders_domains where id='$domain_id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			if (mysql_num_rows($r) > 0) {
				if (!$promocode) {
					$currentUser = GetUserById($_SESSION["userId"]);
					if ($currentUser->specialPromoCode and $currentUser->specialPromoCodeForUser) {
						$promocode = $currentUser->specialPromoCode;
					}
					else if ($currentUser->referal) {
						$referalUser = GetUserById($currentUser->referal);
						if ($referalUser->specialPromoCode and $referalUser->specialPromoCodeForReferals) {
							$promocode = $referalUser->specialPromoCode;
						}
					}
				}


				$r = @mysql_fetch_object($r);
				$zone=GetZoneById($r->zone_id);

				$domainCost = GetDomainCostRenewForUserByZoneId($_SESSION["userId"],$zone->id,0);

				$domainCost=$domainCost * ($domainMonths/12);
				$domainDiscount = $r->discount;
				$domainCost = $domainCost - ($domainCost/100)*$domainDiscount;
				if ($domainDiscount == '100') {$domainCost = 0;}

				if ($r->privacy) {
					$privacyCost = $zone->privacy_cost;
				} else {
					$privacyCost = 0;
				}

				if ($r->localContact) {
					$localContactCost = $zone->localContact_cost;
				} else {
					$localContactCost = 0;
				}

				$domainCost = $domainCost+$privacyCost+$localContactCost;

				if ($promocode and $domainCost > 0) {
					$coupon = GetCoupon("promo",$promocode,1,"zone",$zone->id);
					if (IsCanUseCoupon($coupon->id,$_SESSION["userId"])) {
						$promoId = $coupon->id;
						$promoSkidka = $coupon->value;
						$promoMaxSrok = $coupon->zonesMaxSrok;
						$promoTXT = ", promo-code $promocode";
						$promoCodeTXT = $promocode;
				
						$calcRes = calculatePromoDiscountWithMaxSrok($domainCost, $domainMonths, $promoMaxSrok, $promoSkidka);

						$promoTotal += $calcRes[summDiscount];
						$domainCost = $calcRes[totalSummWithDiscount];
					}
				}
				$domainCost = round($domainCost,2);

				$history = "Домен: <B>$r->domain</B>, ".($domainMonths/12)." г.";
				if ($r->privacy) { $history .= " + <B>".$_lang[OrderPrivacy]."</B>"; }
				if ($r->localContact) { $history .= " + <B>".$_lang[OrderLocalContact]."</B>"; }

				@mysql_query("insert into bills (uid,domain_id,domain_srok,money_domain,created,renew,history,promocode) values('".$_SESSION['userId']."','$domain_id','$domainMonths','$domainCost',NOW(),'1','$history','$promoCodeTXT')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				$bill_id=mysql_insert_id();
				$sid=sprintf("%04d", $bill_id);

			     	addUserLog($_SESSION['userId'], "renewdomain", "$r->domain, ".($domainMonths/12)." ".$_lang[OrderSokraschenieGod].$promoTXT);
				AddUsedByToCoupon($promoId,$_SESSION["userId"],$bill_id);

				$tpl=GetTpl('email_touser_domain_renew', $_SESSION["userLang"]);
				$subject = $tpl[subject]; $template = $tpl[template];

				if ($subject and $template) {
					$attachPDFtoBill = GetSetting("attachPDFtoBill");
					if (($attachPDFtoBill and $_SESSION["userAttachPDFtoBill"] != "2") or (!$attachPDFtoBill and $_SESSION["userAttachPDFtoBill"] == "1")) {
						$profile=GetUserProfileByUserId($_SESSION['userId']);

						if ($profile->org == "3" and $profile->firma and $profile->phone) {
							$attachFile = createFaktura('', $bill_id, 2);
						} else if ($profile->org == "2" and $profile->name and $profile->surname and $profile->phone) {
							$attachFile = createFaktura('', $bill_id, 2);
						} else if ($profile->org == "1") {
							$attachFile = createKvitanciya('', $bill_id, 2);

							if ($profile->name and $profile->surname and $profile->phone) {
								$attachFile2 = createFaktura('', $bill_id, 2);
							}
						}
						if (!$attachFile) {$attachFile="";}
						if (!$attachFile2) {$attachFile2="";}
					}

					$company_name=GetSetting('company_name');
					$company_url=GetSetting('company_url');
					$billing_url=GetSetting('billing_url');
					$support_url=GetSetting('support_url');
					$manager_email=GetSetting('manager_email');

					if ($r->privacy) { $privacyAddon = " + ".$_lang[OrderPrivacy]; }
					if ($r->localContact) { $localContactAddon = " + ".$_lang[OrderLocalContact]; }
	     		
					$template = str_replace('{company_name}',$company_name,$template);
				     	$template = str_replace('{company_url}',$company_url,$template);
				     	$template = str_replace('{billing_url}',$billing_url,$template);
			     		$template = str_replace('{support_url}',$support_url,$template);
				     	$template = str_replace('{domain}',$r->domain.$privacyAddon.$localContactAddon,$template);
				     	$template = str_replace('{srok}',($domainMonths/12),$template);
				     	$template = str_replace('{login}',$_SESSION["userLogin"],$template);
				     	$template = str_replace('{password}',"******",$template);
				     	$template = str_replace('{schet}',$sid,$template);
				     	$template = str_replace('{domaincost}',round($domainCost*CURK,2)." ".CURS,$template);
				     	$template = str_replace('{cost}',round($domainCost*CURK,2)." ".CURS,$template);
				     	$template = str_replace('{userid}',$_SESSION['userId'],$template);
				     	$template = str_replace('{promocode}',$promoCodeTXT,$template);
     	        	
					WriteMailLog($subject,$template,$_SESSION["userId"]);
					sendmail($_SESSION["userEmail"],$company_name,$manager_email,$subject,$template,$attachFile,$attachFile2,$tpl[type]);
					sendmail($_SESSION["userEmail2"],$company_name,$manager_email,$subject,$template,$attachFile,$attachFile2,$tpl[type]);

					$admEmails=GetAdminEmailsWhereTrueParam("sendneworder");
					if (count($admEmails) > 0) {
						WriteMailLog("Duplicate: ".$subject,$template);
					}
					while (list($i,$em) = @each($admEmails)) {
						sendmail($em,'',$manager_email,"Duplicate: ".$subject,$template,$attachFile,$attachFile2,$tpl[type]);
					}

					@unlink($attachFile);
					@unlink($attachFile2);
				}
				mclose();

				Header("Location: billing.php?do=pay&fromreg=1&id=$bill_id");

				exit;

			}
		}
	}

	$r=@mysql_query("select * from orders_domains where id='$domain_id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
	if (mysql_num_rows($r) == 0) {$error=$_lang[ErrorBadId];}

	head('utf-8',$_lang[RenewTitleDomain]);
	print "<H1 class=pagetitle>".$_lang[RenewTitleDomain]."</H1><hr class=hr>";
	if ($error) {print $_lang[Error].": $error<BR><BR>";}

	if (mysql_num_rows($r) > 0) {
		$r=mysql_fetch_object($r);

		?>
		<form method=post>
		<input type=hidden name=do value=<? print $do?>>
		<input type=hidden name=sub value=renew>
		<input type=hidden name=domain_id value=<? print $domain_id?>>
		<table class='rpTableBlank' border=0>
		<tr><td align=right><? print $_lang[RenewDomain]?>:&nbsp;</td><td><input class=input type=text name=domain value="<? print $r->domain?>" readonly></td></tr>
		<?
		$minsrok=@mysql_query("select t1.minsrok_renew from zones as t1, orders_domains as t2 where t1.id=t2.zone_id and t2.id='$domain_id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		$minsrok=mysql_fetch_object($minsrok);
		$minsrok=$minsrok->minsrok_renew;
		?>
		<tr><td align=right><? print $_lang[RenewNaSrok]?>:</td><Td><select class=input name=domainMonths><option value=<? print $minsrok?>><? print ($minsrok/12)?> <? print $_lang[RenewYear]?></option></select></td></tr>
		<? if (GetCouponActiveCount("promo") > 0) { ?>
		<tr><td align=right><? print $_lang[OrderPromoCode]?>:&nbsp;</td><td><input class=input type=text name=promocode value="<? print $promocode?>"></td></tr>
		<? } ?>
		<tr><td colspan=2 align=center><BR><input class=button type=submit value=<? print $_lang[RenewRenew]?>></td></tr>
		</table>
		</form>
		<?
	}
	foot('utf-8');
}

if ($do == "renewshop") {
	$shop_id=@intval($shop_id);
	$shopMonths=@intval($shopMonths);

	if ($sub == "renew") {
		if (!$shopMonths or $shopMonths <= 0) {$error=$_lang[RenewErrorNoSrok];}
		else {
			$r=GetOrderShopById($shop_id,$_SESSION["userId"]);
			if ($r->id) {
				if (!$promocode) {
					$currentUser = GetUserById($_SESSION["userId"]);
					if ($currentUser->specialPromoCode and $currentUser->specialPromoCodeForUser) {
						$promocode = $currentUser->specialPromoCode;
					}
					else if ($currentUser->referal) {
						$referalUser = GetUserById($currentUser->referal);
						if ($referalUser->specialPromoCode and $referalUser->specialPromoCodeForReferals) {
							$promocode = $referalUser->specialPromoCode;
						}
					}
				}

				$shopItem=GetShopItemById($r->item);
				$shopCost = $shopItem->cost;

				$shopSpecCost = GetSpecialCost($_SESSION['userId'],"shop",$shopItem->id);
				if ($shopSpecCost) {
					$shopCost = $shopSpecCost["cost1"];
				}

				if ($shopItem->costtype == "month") { $shopCost=$shopCost * $shopMonths; }
				else if ($shopItem->costtype == "year") { $shopCost=$shopCost * ($shopMonths/12); }

				$tsroki=@mysql_query("select discount from shop_sroki where item='$r->item' and months='$shopMonths' and renew='1'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				$tsroki=mysql_fetch_object($tsroki);
				$srokDiscount=$tsroki->discount;
				$shopDiscount=$r->discount;
				$shopCost = $shopCost - ($shopCost/100) * ($srokDiscount + $shopDiscount);
				if ($shopDiscount == '100') { $shopCost = 0; }

				if ($promocode and $shopCost > 0) {
					$coupon = GetCoupon("promo",$promocode,1,"shopitem",$shopItem->id);
					if (IsCanUseCoupon($coupon->id,$_SESSION["userId"])) {
						$promoId = $coupon->id;
						$promoSkidka = $coupon->value;
						$promoMaxSrok = $coupon->shopItemsMaxSrok;
						$promoTXT = ", promo-code $promocode";
						$promoCodeTXT = $promocode;
				
						$calcRes = calculatePromoDiscountWithMaxSrok($shopCost, $shopMonths, $promoMaxSrok, $promoSkidka);

						$promoTotal += $calcRes[summDiscount];
						$shopCost = $calcRes[totalSummWithDiscount];
					}
				}
				$shopCost = round($shopCost,2);

				$history = "Товар: <B>$shopItem->name</B>";
				if ($shopItem->costtype == "month") { $history .= ", $shopMonths мес."; }
				else if ($shopItem->costtype == "year") { $history .= ", ".($shopMonths/12)." г."; }

				@mysql_query("insert into bills (uid,shop_id,shop_srok,money_shop,created,renew,history,promocode) values('".$_SESSION['userId']."','$shop_id','$shopMonths','$shopCost',NOW(),'1','$history','$promoCodeTXT')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				$bill_id=mysql_insert_id();
				$sid=sprintf("%04d", $bill_id);

			     	addUserLog($_SESSION['userId'], "renewshop", "$shopItem->name, ".$shopMonths." ".$_lang[OrderSokraschenieMonth].$promoTXT);
				AddUsedByToCoupon($promoId,$_SESSION["userId"],$bill_id);

				$tpl=GetTpl('email_touser_shop_renew', $_SESSION["userLang"]);
				$subject = $tpl[subject]; $template = $tpl[template];

				if ($subject and $template) {
					$attachPDFtoBill = GetSetting("attachPDFtoBill");
					if (($attachPDFtoBill and $_SESSION["userAttachPDFtoBill"] != "2") or (!$attachPDFtoBill and $_SESSION["userAttachPDFtoBill"] == "1")) {
						$profile=GetUserProfileByUserId($_SESSION['userId']);
				
						if ($profile->org == "3" and $profile->firma and $profile->phone) {
							$attachFile = createFaktura('', $bill_id, 2);
						} else if ($profile->org == "2" and $profile->name and $profile->surname and $profile->phone) {
							$attachFile = createFaktura('', $bill_id, 2);
						} else if ($profile->org == "1") {
							$attachFile = createKvitanciya('', $bill_id, 2);

							if ($profile->name and $profile->surname and $profile->phone) {
								$attachFile2 = createFaktura('', $bill_id, 2);
							}

						}
						if (!$attachFile) {$attachFile="";}
						if (!$attachFile2) {$attachFile2="";}
					}

					$company_name=GetSetting('company_name');
					$company_url=GetSetting('company_url');
					$billing_url=GetSetting('billing_url');
					$support_url=GetSetting('support_url');
					$manager_email=GetSetting('manager_email');
	     		
					$template = str_replace('{company_name}',$company_name,$template);
				     	$template = str_replace('{company_url}',$company_url,$template);
				     	$template = str_replace('{billing_url}',$billing_url,$template);
				     	$template = str_replace('{support_url}',$support_url,$template);
			     		$template = str_replace('{item}',$shopItem->name,$template);
				     	$template = str_replace('{srok}',$shopMonths,$template);
				     	$template = str_replace('{login}',$_SESSION["userLogin"],$template);
				     	$template = str_replace('{password}',"******",$template);
				     	$template = str_replace('{schet}',$sid,$template);
			     		$template = str_replace('{itemcost}',round($shopCost*CURK,2)." ".CURS,$template);
				     	$template = str_replace('{cost}',round($shopCost*CURK,2)." ".CURS,$template);
				     	$template = str_replace('{userid}',$_SESSION['userId'],$template);
				     	$template = str_replace('{promocode}',$promoCodeTXT,$template);
     	
					WriteMailLog($subject,$template,$_SESSION["userId"]);
					sendmail($_SESSION["userEmail"],$company_name,$manager_email,$subject,$template,$attachFile,$attachFile2,$tpl[type]);
					sendmail($_SESSION["userEmail2"],$company_name,$manager_email,$subject,$template,$attachFile,$attachFile2,$tpl[type]);

					$admEmails=GetAdminEmailsWhereTrueParam("sendneworder");
					if (count($admEmails) > 0) {
						WriteMailLog("Duplicate: ".$subject,$template);
					}
					while (list($i,$em) = @each($admEmails)) {
						sendmail($em,'',$manager_email,"Duplicate: ".$subject,$template,$attachFile,$attachFile2,$tpl[type]);
					}	

					@unlink($attachFile);
					@unlink($attachFile2);
				}
				mclose();

				Header("Location: billing.php?do=pay&fromreg=1&id=$bill_id");

				exit;

			}
		}
	}

	$r=GetOrderShopById($shop_id,$_SESSION["userId"]);
	if (!$r->id) {$error=$_lang[ErrorBadId];}

	head('utf-8',$_lang[RenewTitleShop]);
	print "<H1 class=pagetitle>".$_lang[RenewTitleShop]."</H1><hr class=hr>";
	if ($error) {print $_lang[Error].": $error<BR><BR>";}

	if ($r->id) {
		$shopItem=GetShopItemById($r->item);
		$shop_sroki=@mysql_query("select * from shop_sroki where item='$shopItem->id' and renew='1' order by months") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		?>
		<form method=post>
		<input type=hidden name=do value=<? print $do?>>
		<input type=hidden name=sub value=renew>
		<input type=hidden name=shop_id value=<? print $shop_id?>>
		<table class='rpTableBlank' border=0>
		<tr><td align=right><? print $_lang[RenewShop]?>:&nbsp;</td><td><? print $shopItem->name?></td></tr>
		<tr><td align=right><? print $_lang[RenewNaSrok]?>:&nbsp;</td><td><select class=input name=shopMonths><option></option><?

		if (mysql_num_rows($shop_sroki) > 0) {
			while ($srok = @mysql_fetch_object($shop_sroki)) {
				if ($shopItem->costtype == "month") {$sokrashenie = $_lang[OrderSokraschenieMonth]; $m = $srok->months;}
				else if ($shopItem->costtype == "year") {$sokrashenie = $_lang[OrderSokraschenieGod]; $m = $srok->months / 12;}


				if ($srok->months == $shopMonths) {$selected='selected';} else {$selected='';}
				if ($srok->discount) {$skidka=" (".$_lang[OrderDiscountSmall]." $srok->discount%)";} else {$skidka="";}
				print "<option value=$srok->months $selected>$m ".$sokrashenie.$skidka."</option>";
			}
		}
		?>
		</select></td></tr>
		<? if (GetCouponActiveCount("promo") > 0) { ?>
		<tr><td align=right><? print $_lang[OrderPromoCode]?>:&nbsp;</td><td><input class=input type=text name=promocode value="<? print $promocode?>"></td></tr>
		<? } ?>
		<tr><td colspan=2 align=center><BR><input class=button type=submit value=<? print $_lang[RenewRenew]?>></td></tr>
		</table>
		</form>
		<?
	}
	foot('utf-8');
}

if ($do == "partner") {
	head('utf-8',$_lang[PartnerTitle]);
	print "<H1 class=pagetitle>".$_lang[PartnerTitle]."</H1><hr class=hr>";

	if (GetSetting("partnerEnable")) {
		$partnerMoney = GetUserPartnerMoney($_SESSION["userId"]);
		$minOut = GetSetting("partnerMinMoneyOut");
		$moneyOut=floatval($moneyOut); 
		$partnerLevels = GetSetting("partnerLevels");
		$partnerAllowSendMoney=GetSetting("partnerAllowSendMoney");
		$partnerMinActiveReferalsMoneyOut=GetSetting("partnerMinActiveReferalsMoneyOut");

		if ($sub == "out") {
			$moneyOut=floatval($moneyOut); $moneyOut = round($moneyOut/CURK,2);

			if ($partnerMinActiveReferalsMoneyOut) { $userActiveReferals = GetUserActiveReferalsLevel1Count($_SESSION["userId"]); }

			if ($moneyOut < $minOut) { print "<font color=red>".$_lang[PartnerErrorMinOut]." ".round($minOut*CURK,2)." ".CURS."</font><br><br>"; }
			else if ($moneyOut > $partnerMoney) { print "<font color=red>".$_lang[PartnerErrorNoMoneyOnPartner]."</font><br><br>"; }
			else if ($partnerMinActiveReferalsMoneyOut and $userActiveReferals < $partnerMinActiveReferalsMoneyOut) { print "<font color=red>".$_lang[PartnerErrorNoMinActiveReferals]."</font><br><br>"; }
			else if (!$paymethod) { print "<font color=red>".$_lang[PartnerErrorNoPayMethod]."</font><br><br>"; }
#			else if (!$schet) { print "<font color=red>".$_lang[PartnerNoPurse]."</font><br><br>"; }
			else {
				@mysql_query("update users set partnerMoney=partnerMoney-$moneyOut where id=".$_SESSION["userId"]) or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			     	addUserLog($_SESSION['userId'], "moneyOut", $_lang[PartnerMoneyOut]." ".round($moneyOut*CURK,2)." ".CURS." via $paymethod ($schet)");

				$subject = "Заказ на вывод средств [".$_SESSION["userLogin"].", ".round($moneyOut*CURK,2)." ".CURS."]";
				$message = "Пользователь ".$_SESSION["userLogin"]." заказал вывод средств с партнерского счета.\r\n\r\nСумма: ".round($moneyOut*CURK,2)." ".CURS."\r\nСпособ вывода: $paymethod\r\nНомер счета/кошелька: $schet\r\n\r\n�?мейте ввиду, что средства уже сняты с партнерского счета клиента.";

				$manager_email = GetSetting("manager_email");
		     		
				$admEmails=GetAdminEmailsWhereTrueParam("sendmoneyout");
				if (count($admEmails) > 0) {
					WriteMailLog($subject,$message);
				}
				while (list($i,$em) = @each($admEmails)) {
					sendmail($em,'',$manager_email,$subject,$message);
				}
				print $_lang[PartnerMoneyOutSuccess]."<br><br>";
				$partnerMoney = GetUserPartnerMoney($_SESSION["userId"]);
			
			}
		}
		else if ($sub == "send") {
			$moneyOut=floatval($moneyOut); $moneyOut = round($moneyOut/CURK,2);

			if (!$partnerAllowSendMoney) { print "<font color=red>".$_lang[PartnerErrorSendMoneyOff]."</font><br><br>"; }
			else if ($moneyOut < $minOut) { print "<font color=red>".$_lang[PartnerErrorMinOut]." ".round($minOut*CURK,2)." ".CURS."</font><br><br>"; }
			else if ($moneyOut > $partnerMoney) { print "<font color=red>".$_lang[PartnerErrorNoMoneyOnPartner]."</font><br><br>"; }
			else if (!$userLogin) { print "<font color=red>".$_lang[PartnerErrorNoUserLogin]."</font><br><br>"; }
			else {
				$userMoneyTo = GetUserByLogin($userLogin);
				if (!$userMoneyTo->id) { print "<font color=red>".$_lang[PartnerErrorNoUserFound]."</font><br><br>"; }
				else if ($userMoneyTo->id == $_SESSION["userId"]) { print "<font color=red>".$_lang[PartnerErrorNoSendItself]."</font><br><br>"; }
				else {
					@mysql_query("update users set partnerMoney=partnerMoney-$moneyOut where id='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
					@mysql_query("update users set money=money+$moneyOut where id='".$userMoneyTo->id."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				     	addUserLog($_SESSION['userId'], "moneyOut", "Send partner money ".round($moneyOut*CURK,2)." ".CURS." to user # $userMoneyTo->id, $userMoneyTo->login");
				     	addUserLog($userMoneyTo->id, "moneyIn", "Receive partner money ".round($moneyOut*CURK,2)." ".CURS." from user # ".$_SESSION['userId'].", ".$_SESSION['userLogin']);

					print $_lang[PartnerMoneySendSuccess]."<br><br>";
					$partnerMoney = GetUserPartnerMoney($_SESSION["userId"]);
				}
			}
		}

		print $_lang[PartnerPartnerSchet].": ".round($partnerMoney*CURK,2)." ".CURS."<br>";

		$refs1array = array();
		$refs2array = array();

		$referals = GetUserReferalsArray($_SESSION["userId"]);
		while (list($id,$refArray) = @each($referals)) {      
#			$refs1array[$id] = $refArray[login];
			$refs1array[$id] = $id;

			$referals2 = GetUserReferalsArray($id);
			while (list($id2,$refArray2) = @each($referals2)) {
#				$refs2array[$id2] = $refArray2[login];
				$refs2array[$id2] = $id2;
			}
		}
		$refs1cnt = count($refs1array);
		$refs2cnt = count($refs2array);

		print $_lang[PartnerReferalsLevel1].": <a title='".$_lang[PartnerReferalsLevel1]."'>$refs1cnt</a>";
		if ($partnerLevels == "2") { print " / <a title='".$_lang[PartnerReferalsLevel2]."'>$refs2cnt</a>"; }
		print " [ <a href=?do=$do&sub=stats class=rootlink>".$_lang[PartnerStats]." ]</a><BR><BR>";

		print $_lang[PartnerPartnerLink].": <b>".$full_www_path."pl.php?".$_SESSION["userId"]."</b><br><br>";

		if ($sub == "stats") {
			$r = @mysql_query("select * from partner_stats where uid='".$_SESSION["userId"]."' order by dt desc") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			if (mysql_num_rows($r) > 0) {
				print "<table class='rpTable'><tr class=$font_head><td align=center><B>".$_lang[PartnerReferalId]."</b></td><td align=center><B>".$_lang[PartnerReferalDate]."</b></td><td align=center><B>".$_lang[PartnerReferalSumIn]."</b></td><td align=center><B>".$_lang[PartnerReferalSumPartner]."</b></td></tr>";
				while ($rr = @mysql_fetch_object($r)) {
					getfont();
					print "<tr class=$font_row align=center>";
					print "<td># $rr->referal";
					if (@in_array($rr->referal,$refs2array)) { $ref2 = GetUserById($rr->referal); print " [ ".$_lang[PartnerReferalsLevel2Min]." ] # $ref2->referal"; }
					print "</td>";
					print "<td>$rr->dt</td>";
					print "<td>".round($rr->sumin*CURK,2)." ".CURS."</td>";
					print "<td>".round($rr->sumpartner*CURK,2)." ".CURS."</td>";
					print "</tr>";
				}
				print "</table>";
			} else {
				print "<font color=red>".$_lang[PartnerErrorNoStats]."</font>";
			}
			print "<BR>";
		}

		if ($sub != "stats") {
			if ($partnerMoney > 0) {
				print "<b>".$_lang[PartnerZakazatVivod].":</b><br>";
				print "<form method=post><table class='rpTableBlank'>";
				print "<input type=hidden name=do value=$do>";
				print "<input type=hidden name=sub value=out>";
				print "<tr><Td>".$_lang[PartnerSumma].", ".CURS.":</td><td><input class=input type=text name=moneyOut value='' size=5></td></tr>";
				print "<tr><Td>".$_lang[PartnerSposobVivoda].":</td><td>"; printPayMethodsSelect(1); print "</td></tr>";
				print "<tr><td>".$_lang[PartnerPurse].":</td><Td><input class=input type=text name=schet size=13></td></tr>";
				print "<tr><td colspan=2 align=center><input class=button type=submit value='".$_lang[PartnerZakazatVivodButton]."'></td></tr>";
				print "</table></form>";
			}

			if ($partnerMoney > 0 and $partnerAllowSendMoney) {
				print "<b>".$_lang[PartnerSendMoney].":</b><br>";
				print "<form method=post><table class='rpTableBlank'>";
				print "<input type=hidden name=do value=$do>";
				print "<input type=hidden name=sub value=send>";
				print "<tr><Td>".$_lang[PartnerSumma].", ".CURS.":</td><td><input class=input type=text name=moneyOut value='' size=5></td></tr>";
				print "<tr><td>".$_lang[PartnerSendUser].":</td><Td><input class=input type=text name=userLogin size=13></td></tr>";
				print "<tr><td colspan=2 align=center><input class=button type=submit value='".$_lang[PartnerSendMoneyButton]."'></td></tr>";
				print "</table></form>";
			}

			$handle=opendir("./_rootimages/banners/");
			while ($file = readdir($handle)) {
				$f = mb_split("\.",$file);
				if ($file != "." and $file != ".." and $file != "" and !preg_match("/\.code$/iu", $file) and !preg_match("/^cat_/iu", $file) and (preg_match ("/gif/ui", $f[1]) or preg_match ("/jpg/ui", $f[1]) or preg_match ("/jpeg/ui", $f[1]) or preg_match ("/png/ui", $f[1]) or preg_match ("/swf/iu", $f[1]))) {$files[] = $file;}
			}
			closedir($handle);
			@sort($files);
			$cnt=count($files);

			if ($cnt > 0) {
				print "<table class='rpTable'><tr><td align=center class=$font_head><B>".$_lang[PartnerOurBanners]."</b></td></tr>";
				while (list($k,$v) = @each($files)) {
					$code = GetBannerCodeByFileName($v,$full_www_path."pl.php?".$_SESSION["userId"]);

					getfont();
					print "<tr class=$font_row align=center><td>$code<br><textarea class=input cols=70 rows=5 readonly scrolling=yes>$code</textarea><br><Br></td></tr>";
				}
				print "</table>";
			}
		}
	} else {
		print $_lang[PartnerErrorPartnerOff];
	}

	foot('utf-8');
}

if ($do == "tickets") {
	head('utf-8',$_lang[TicketsTitle]);
	print "<H1 class=pagetitle>".$_lang[TicketsTitle]."</H1><hr class=hr>";

	if (GetSetting("ticketsEnable")) {

	if ($sub == "delete" and $id and GetSetting("ticketsUsersCanDelete")) {
		$r=@mysql_query("select * from tickets where id='$id' and userid=".$_SESSION["userId"]) or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		if (mysql_num_rows($r) > 0) {
			@mysql_query("delete from tickets where id='$id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			@mysql_query("delete from tickets where parentid='$id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			print $_lang[TicketsDeleteSuccess]."<br><br>";

			print "[ <a class=rootlink href=?do=tickets>".$_lang[TicketsGotoTicketsList]."</a> ]<BR><BR>";
		} else {
			$sub = "";
		}
	}

	if ($sub == "close" and $id) {
		$z = @mysql_query("select * from tickets where status='closed' and id='$id' and userid=".$_SESSION["userId"]) or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		if (mysql_num_rows($z) == 0) {
			@mysql_query("update tickets set status='closed' where id='$id' and userid=".$_SESSION["userId"]) or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			@mysql_query("insert into tickets (parentid,dt,userid,message) values('$id',NOW(),'".$_SESSION["userId"]."','Тикет закрыт клиентом.')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			print $_lang[TicketsCloseSuccess]."<br><br>";

			print "[ <a class=rootlink href=?do=tickets&sub=view&id=$id>".$_lang[TicketsGoBackToTicket]."</a> ] [ <a class=rootlink href=?do=tickets>".$_lang[TicketsGotoTicketsList]."</a> ]<BR><BR>";
		} else {
			$sub = "";
		}
	}

	if ($sub == "new") {
		if (!$subject) { print "<font color=red>".$_lang[TicketsErrorSubject]."</font><br><br>"; }
		else if ($priority == '') { print "<font color=red>".$_lang[TicketsErrorPriority]."</font><br><br>"; }
		else if (!$message) { print "<font color=red>".$_lang[TicketsErrorMessage]."</font><br><br>"; }
		else {
			$z=@mysql_query("select * from tickets where parentid='0' and priority='$priority' and subject='$subject' and userid='".$_SESSION["userId"]."' and message='".addslashes($message)."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			if (mysql_num_rows($z) > 0) {
				$sub = "";
			} else {
				@mysql_query("insert into tickets (priority,dt,subject,userid,message,department,sendsms) values('$priority',NOW(),'$subject','".$_SESSION["userId"]."','".addslashes($message)."','$department','$sendsms')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				$ticketid=mysql_insert_id();

				if (GetSetting("ticketsUsersCanAttach")) {
					$attached = array();
					for ($i=0; $i < count($_FILES['userfile']['name']); $i++) {
						if ($_FILES['userfile']['name'][$i]) {
							if ($_FILES['userfile']['name'][$i] != '' and $_FILES['userfile']['type'][$i] != '' and $_FILES['userfile']['tmp_name'][$i] != '') {
								preg_match("/^(.+)\.([^\.]+)$/ui",$_FILES['userfile']['name'][$i],$arr);
								$filename = $arr[1]; $fileext = $arr[2];

								$newFile = "ticket_".$ticketid."_".$filename.".".$fileext;

								$file=$full_home_path."/_rootfiles/".$newFile;

								if (!file_exists($file)) {
									if (move_uploaded_file($_FILES['userfile']['tmp_name'][$i],$file)) {
										@chmod($file, 0777);

										$attached[] = "$newFile::$filename.$fileext";

										print $_lang[TicketsFileNumber].($i+1)." ".$_lang[TicketsFileAttachSuccess].".<BR>";
									} else {
										print $_lang[TicketsFileAttachError]." ".$_lang[TicketsFileNumber].($i+1).".<BR>";
									}
								} else {
									print $_lang[TicketsFileAttachError]." ".$_lang[TicketsFileNumber].($i+1)." - ".$_lang[TicketsFileAttachFileExists].".<BR>";
								}
							}
						}
					}
					if (@count($attached) > 0) {
						$attached = @join(":x:",$attached);

						@mysql_query("update tickets set attachedFiles='$attached' where id='$ticketid'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
					} else {
						$attached = "";
					}
				}

				$support_email=GetSetting("support_email");
				$subject_msg = "[#$ticketid] New ticket [важность ".$_priority[$priority]."]";
				$message = "Пользователь ".$_SESSION["userLogin"]." открыл новый тикет \"$subject\" (ID # $ticketid)\n\nПерейти к тикету можно по ссылке: ".$full_www_path.$admin_script."?do=tickets&sub=view&id=$ticketid\n\nСообщение:\n\n$message";

				$admEmails=GetAdminEmailsWhereTrueParam("sendticket",$department);
				if (count($admEmails) > 0) {
					WriteMailLog($subject_msg,$message);
				}
				while (list($i,$em) = @each($admEmails)) {
					sendmail($em,'',$support_email,$subject_msg,$message);
				}

				if (GetSetting("smsGateway")) {
					$smsmsg = "[".$_prioritysms[$priority]."] New ticket #$ticketid: $subject";

					$admIds=GetAdminIdsWhereTrueParam("sms_sendticketnew",$department);
					while (list($i,$aid) = @each($admIds)) {
						$smsAdmin=GetAdminById($aid);
						if ($smsAdmin->mobile) {
							sendSMS('',$aid,$smsmsg);
						}
					}
				}

				print $_lang[TicketsNewSuccess]."<br><br>";

				print "[ <a class=rootlink href=?do=tickets&sub=view&id=$ticketid>".$_lang[TicketsGotoNewTicket]."</a> ] [ <a class=rootlink href=?do=tickets>".$_lang[TicketsGotoTicketsList]."</a> ]<BR><BR>";
			}
		}
	}

	if ($sub == "reply") {
		if (!$id) {print "<font color=red>".$_lang[ErrorBadId]."</font><br><br>";}
		else {
			$r=@mysql_query("select * from tickets where id='$id' and userid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			if (mysql_num_rows($r) == 0) {print "<font color=red>".$_lang[TicketsErrorNoTicket]."</font><br><br>";}
			else if (!$message) { print "<font color=red>".$_lang[TicketsErrorMessage]."</font><br><br>";}
			else { 
				$z=@mysql_query("select * from tickets where parentid='$id' and userid='".$_SESSION["userId"]."' and message='".addslashes($message)."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				if (mysql_num_rows($z) > 0) {
					$sub = "";
				} else {
					@mysql_query("update tickets set newforadmin='1',status='open',sendsms='$sendsms' where id='$id' and userid=".$_SESSION["userId"]) or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
					@mysql_query("insert into tickets (parentid,dt,userid,message) values('$id',NOW(),'".$_SESSION["userId"]."','".addslashes($message)."')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
					$reply_id = @mysql_insert_id();

					if (GetSetting("ticketsUsersCanAttach")) {
						$attached = array();
						for ($i=0; $i < count($_FILES['userfile']['name']); $i++) {
							if ($_FILES['userfile']['name'][$i]) {
								if ($_FILES['userfile']['name'][$i] != '' and $_FILES['userfile']['type'][$i] != '' and $_FILES['userfile']['tmp_name'][$i] != '') {
									preg_match("/^(.+)\.([^\.]+)$/ui",$_FILES['userfile']['name'][$i],$arr);
									$filename = $arr[1]; $fileext = $arr[2];

									$newFile = "ticket_".$reply_id."_".$filename.".".$fileext;

									$file=$full_home_path."/_rootfiles/".$newFile;

									if (!file_exists($file)) {
										if (move_uploaded_file($_FILES['userfile']['tmp_name'][$i],$file)) {
											@chmod($file, 0777);
				
											$attached[] = "$newFile::$filename.$fileext";
		
											print $_lang[TicketsFileNumber].($i+1)." ".$_lang[TicketsFileAttachSuccess].".<BR>";
										} else {
											print $_lang[TicketsFileAttachError]." ".$_lang[TicketsFileNumber].($i+1).".<BR>";
										}
									} else {
										print $_lang[TicketsFileAttachError]." ".$_lang[TicketsFileNumber].($i+1)." - ".$_lang[TicketsFileAttachFileExists].".<BR>";
									}
								}
							}
						}
						if (@count($attached) > 0) {
							$attached = @join(":x:",$attached);
						
							@mysql_query("update tickets set attachedFiles='$attached' where id='$reply_id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
						} else {
							$attached = "";
						}
					}

					$ticket=GetTicketById($id);
					$support_email=GetSetting("support_email");

					$subject = "[#$id] Reply to ticket [важность ".$_priority[$ticket->priority]."]";
					$message = "Пользователь ".$_SESSION["userLogin"]." добавил новое сообщение в тикет \"".$ticket->subject."\" (ID # $id)\n\nПерейти к тикету можно по ссылке: ".$full_www_path.$admin_script."?do=tickets&sub=view&id=$id\n\nСообщение:\n\n$message";

					$admEmails=GetAdminEmailsWhereTrueParam("sendticket",$ticket->department);
					if (count($admEmails) > 0) {
						WriteMailLog($subject,$message);
					}
					while (list($i,$em) = @each($admEmails)) {
						sendmail($em,'',$support_email,$subject,$message);
					}

					if (GetSetting("smsGateway") and $ticket->sendadminsms) {
						$smsmsg = "[".$_prioritysms[$ticket->priority]."] New ticket reply #$id: $ticket->subject";

						$lastAdminId = @mysql_query("select adminId from tickets where (id='$id' or parentid='$id') and adminId > 0 order by id desc limit 0,1") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
						$lastAdminId = @mysql_fetch_object($lastAdminId);
						$lastAdminId = $lastAdminId->adminId;

						$admIds=GetAdminIdsWhereTrueParam("sms_sendticketreply");
						if (@in_array($lastAdminId,$admIds)) {
							$smsAdmin=GetAdminById($lastAdminId);
							if ($smsAdmin->mobile) {
								sendSMS('',$lastAdminId,$smsmsg);
							}
						}
					}

					$message="";
					print $_lang[TicketsReplySuccess]."<br><br>";

					print "[ <a class=rootlink href=?do=tickets&sub=view&id=$id>".$_lang[TicketsGoBackToTicket]."</a> ] [ <a class=rootlink href=?do=tickets>".$_lang[TicketsGotoTicketsList]."</a> ]<BR><BR>";
				}
			}
		}
	}

	if ($sub == "view") {
		if (!$id) {print "<font color=red>".$_lang[ErrorBadId]."</font><br><br>";}
		else {
			$r=@mysql_query("select * from tickets where id='$id' and userid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			$r2=@mysql_query("select t1.* from tickets as t1, tickets as t2 where t1.parentid='$id' and t2.id=t1.parentid and t2.userid='".$_SESSION["userId"]."' order by t1.id") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

			if (mysql_num_rows($r) == 0) {print "<font color=red>".$_lang[TicketsErrorNoTicket]."</font><br><br>";}
			else {
				@mysql_query("update tickets set newforuser='0' where id='$id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

				print "<table class='rpTable' cellpadding=3>";
				while ($rr = @mysql_fetch_object($r) or $rr = @mysql_fetch_object($r2)) {
					$cnt++;

					if ($cnt == 1 and ($rr->adminname or $rr->adminId)) { $firstMsgByAdmin = true; }

					if ($rr->userid and !$rr->adminId and !$rr->adminname) {
						if (!$firstMsgByAdmin) { $type=$_lang[TicketsTicketTypeMsg]; } else { $type=$_lang[TicketsTicketTypeReply]; }
						$img="<img src=\"./_rootimages/ticket_user.gif\">";
						$rating = "";
					} else {
						if ($rr->adminId) {
							$admin = GetAdminById($rr->adminId);
							if ($admin->name) {
								$adminNameT=" ($admin->name)";
							} else if ($rr->adminname) {
								$adminNameT=" ($rr->adminname)";
							} else {
								$adminNameT="";
							}
						} else if ($rr->adminname) {
							$adminNameT=" ($rr->adminname)";
						} else {
							$adminNameT="";
						}

						if ($firstMsgByAdmin) {	$type=$_lang[TicketsTicketTypeMsg]; } else { $type=$_lang[TicketsTicketTypeReply]; }
						$type=$type."$adminNameT"; 

						if ($admin->avatar and file_exists($full_home_path."/_rootimages/avatars/".$admin->avatar)) {
							$img = "<img src=\"./_rootimages/avatars/".$admin->avatar."\">";
						} else {
							$img = "<img src=\"./_rootimages/ticket_admin.gif\">";
						}


						if ($cnt > 1) {
							if ($rr->rating) {
								$rating = " | ".$_lang[TicketsRating].": <span id='$rr->id'></span><script type='text/javascript'> SmartStars.init('$rr->id', null, -$rr->rating, 5, './_rootimages/offstar.gif', './_rootimages/onstar.gif'); </script>";
							} else {
								$rating = " | ".$_lang[TicketsRating].": <span id='$rr->id'></span><script type='text/javascript'> SmartStars.init('$rr->id', null, $rr->rating, 5, './_rootimages/offstar.gif', './_rootimages/onstar.gif', null, function(){ sendRating( 'rating.php?type=tickets', '$rr->id' ); } ); </script>";
							}
						}
					}
					if ($cnt == 1) {
						$sendsms = $rr->sendsms;

						$priority="| ".$_lang[TicketsPriority].": <img src=\"./_rootimages/priority_".$rr->priority.".gif\" alt=\"".$_lang[TicketsPriority]." ".$_priority[$rr->priority]."\">";
						$status="| ".$_lang[TicketsStatus].": <img src=\"./_rootimages/ticket_".$rr->status.".gif\" alt=\"".$_lang[TicketsTicket]." ".$_statusTicket[$rr->status]."\">";

						$ticketStatus = $rr->status;

						print "<tr><td align=center colspan=2 class=$font_head>".$_lang[TicketsTicketView].": <B>".$rr->subject."</b></td></tr>";
					} 
					else {$priority="";$status="";}
					if (preg_match("/\r\n/u", $rr->message)) { $rr->message = preg_replace("/\r\n/u", "<BR>", $rr->message); } else if (preg_match("/\r/u", $rr->message)) { $rr->message = preg_replace("/\r/u", "<BR>", $rr->message); }
					$rr->message = convertToLinks($rr->message);

					if ($rr->attachedFiles) {
						$attachedPrint=array();
						$atatchedFiles = mb_split(":x:",$rr->attachedFiles);
						while (list($mm,$oneFile) = @each($atatchedFiles)) {
							$oneFile = @mb_split("::",$oneFile);
							$attachedPrint[] = "<a href='?do=download&type=ticket&id=$id&msgid=$rr->id&file=$oneFile[1]' class=rootlink>$oneFile[1]</a>";
						}

						$attachedPrint = "<B>Прикрепленные файлы:</B> ".@join(", ",$attachedPrint);
					} else {
						$attachedPrint = "";
					}

					print "<tr><td class=$font_row2 colspan=2><b>#$cnt $type</b> | ".$_lang[TicketsDate].": $rr->dt $priority $status $rating</td></tr>";
					print "<tr class=$font_row1><td valign=top width=60>$img</td><td valign=top>$rr->message<Br><br>$attachedPrint</td></tr>";
				}

				print "</table>";

				if ($ticketStatus == "open" or GetSetting("ticketsUsersCanOpen")) {
					?>
				        <table class='rpTable' border=0><form method=post action="billing.php" enctype="multipart/form-data">
			        	<tr><td colspan=2 align=center class=<? print $font_head?>><B><? print $_lang[TicketsAddMessage]?></b></td></tr>
				        <input type=hidden name=do value=<? print $do?>>
				        <input type=hidden name=sub value=reply>
				        <input type=hidden name=id value=<? print $id?>>
				        <tr><td valign=top><? print $_lang[TicketsTicketTypeMsg]?>:</td><td><textarea class=ticketsTextArea name=message cols=80 rows=12><? print $message?></textarea></td></tr>
					<?
					$user = GetUserById($_SESSION["userId"]);
					if (GetSetting("smsGateway") and GetSetting("smsUserTicketReply") and $user->mobile) {
						?><tr><td></td><td><input type=checkbox name=sendsms value=1 class=input <? if ($sendsms) {print "checked";} ?>> <? print $_lang[TicketsSendSMS]?></td></tr><?
					}

					if (GetSetting("ticketsUsersCanAttach")) {
						?>
				        	<tr><td colspan=2 align=center onclick="myShow('s1');" onmouseover="this.style.cursor='pointer'" class=<? print $font_head?>><B><? print $_lang[TicketsAttachFiles]?></b></td></tr>
						<tr><td colspan=2>
							<div id="s1" style="display: none;">
							<table width=100%>
							<tr><td><? print $_lang[TicketsFileNumber]?>1:</td><td><input type=file name=userfile[0]></td><td><? print $_lang[TicketsFileNumber]?>5:</td><td><input type=file name=userfile[4]></td></tr>
							<tr><td><? print $_lang[TicketsFileNumber]?>2:</td><td><input type=file name=userfile[1]></td><td><? print $_lang[TicketsFileNumber]?>6:</td><td><input type=file name=userfile[5]></td></tr>
							<tr><td><? print $_lang[TicketsFileNumber]?>3:</td><td><input type=file name=userfile[2]></td><td><? print $_lang[TicketsFileNumber]?>7:</td><td><input type=file name=userfile[6]></td></tr>
							<tr><td><? print $_lang[TicketsFileNumber]?>4:</td><td><input type=file name=userfile[3]></td><td><? print $_lang[TicketsFileNumber]?>8:</td><td><input type=file name=userfile[7]></td></tr>
							</table>
							</div>
						</td></tr>
						<?
					}
					?>
				        <tr><td colspan=2 align=center class=<? print $font_head?>><input class=button type=Submit value="<? print $_lang[Add]?>"></td></tr></table><BR></form>
					<?
				}

			}
		}
	}

	if (!$sub) {
		if (!$status or $status == "open") {$status="and (status='open' or status='wait')"; $statustxt=$_lang[TicketsStatusOpen];}
		else if ($status == "wait") {$status="and status='wait'"; $statustxt=$_lang[TicketsStatusWait];}
		else if ($status == "all") {$status=""; $statustxt=$_lang[TicketsStatusAll];}
		else {$status="and status='closed'"; $statustxt=$_lang[TicketsStatusClosed];}

		$ticketsUsersCanDelete = GetSetting("ticketsUsersCanDelete");

	        ?>
	        <table class='rpTable' cellpadding=3>
	        <tr><td colspan=6 align=center class=<? print $font_head?>><B><? print $_lang[TicketsYourTickets]?> [ <? print $statustxt?> ]</b></td></tr>
		<tr><td colspan=6 align=center class=<? print $font_head?>><? print $_lang[TicketsShow]?>: [ <a class=rootlink href=?do=tickets&status=all><? print $_lang[TicketsStatusAll]?></a> ] [ <a class=rootlink href=?do=tickets&status=open><? print $_lang[TicketsStatusOpen]?></a> ] [ <a class=rootlink href=?do=tickets&status=closed><? print $_lang[TicketsStatusClosed]?></a> ] [ <a class=rootlink href=?do=tickets&status=wait><? print $_lang[TicketsStatusWait]?></a> ]</td></tr>
	        <tr class=<? print $font_head?> align=center><td>#</td><td></td><td><? print $_lang[TicketsSubject]?></td><td><? print $_lang[TicketsDate]?></td><td><? print $_lang[TicketsTicketReplys]?></td><td></td></tr>
	        <?
	        $r=@mysql_query("select * from tickets where userid='".$_SESSION["userId"]."' $status and parentid=0 order by newforuser desc, id desc") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
	        $cnt=0;
	        while ($rr = @mysql_fetch_object($r)) {
			getfont();
			$cnt++;

			$subj=$rr->subject; if ($rr->newforuser) {$subj="<b>$subj</b>";}
			$dt = mb_split(' ', $rr->dt); $dt=mydate($dt[0]);

			$replys=@mysql_query("select COUNT(*) as cnt from tickets where parentid='$rr->id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			$replys=mysql_fetch_object($replys);

			$link="?do=$do&sub=view&id=$rr->id";
			if ($ticketsUsersCanDelete) { $delete="<A class=rootlink href=?do=$do&sub=delete&id=$rr->id onclick=\"javascript: return confirm('".$_lang[TicketsDeleteAlert]."');\"><img src=./_rootimages/del.gif border=0 alt='".$_lang[TicketsDelete]."'></a><BR>"; } else { $delete=''; }
			$close="<A class=rootlink href=?do=$do&sub=close&id=$rr->id onclick=\"javascript: return confirm('".$_lang[TicketsCloseAlert]."');\"><img src=./_rootimages/close.gif border=0 alt='".$_lang[TicketsClose]."'></a>";

			?>
			<tr class="<? print $font_row?>" height=30>
			<td valign=middle>&nbsp;<? print $cnt?>&nbsp;</td>
			<td valign=middle>&nbsp;<img src="./_rootimages/priority_<? print $rr->priority?>.gif" alt="<? print $_lang[TicketsPriority]?> <? print $_priority[$rr->priority]?>"> <img src="./_rootimages/ticket_<? print $rr->status?>.gif" alt="<? print $_lang[TicketsTicket]?> <? print $_statusTicket[$rr->status]?>">&nbsp;</td>
			<td>&nbsp;<a class=rootlink href=<? print $link?>><? print $subj?></a>&nbsp;</td>
			<td>&nbsp;<? print $dt?>&nbsp;</td>
			<td align=center><? print $replys->cnt?></td>
			<td align=center><? print $delete?> <?if ($rr->status == "open") {print $close;}?></td>
			</tr>
			<?
		}
		?>
		<tr class=<? print $font_head?>><Td colspan=6><? print $_lang[TicketsTotalTickets]?>: <? print $cnt?></td></td></tr>
		</table><br>

	        <table class='rpTable' border=0><form method=post action="billing.php" enctype="multipart/form-data">
        	<tr><td colspan=2 align=center class=<? print $font_head?>><B><? print $_lang[TicketsNewTicket]?></b></td></tr>
	        <input type=hidden name=do value=<? print $do?>>
	        <input type=hidden name=sub value=new>
	        <tr><td><? print $_lang[TicketsSubject]?>:</td><td><input class=ticketsSubjectInput type=text size=73 maxlength=50 name=subject value="<? print $subject?>" size=49></td></tr>
		<?
		$admGroups = GetAdminGroups();
		if (@mysql_num_rows($admGroups) > 0) {
			print "<tr><td>".$_lang[TicketsDepartment].":</td><td><select class=input name=department>";
			while ($admGroup = @mysql_fetch_object($admGroups)) {
				print "<option value=$admGroup->id> $admGroup->name ";
			}
			print "</select></td></tr>";
		}
		?>
	        <tr><td><? print $_lang[TicketsPriority]?>:</td><td><?GetPrioritySelect($priority)?></td></tr>
	        <tr><td valign=top><? print $_lang[TicketsTicketTypeMsg]?>:</td><td><textarea class=ticketsTextArea name=message cols=80 rows=12><? print $message?></textarea></td></tr>
		<?
		$user = GetUserById($_SESSION["userId"]);
		if (GetSetting("smsGateway") and GetSetting("smsUserTicketReply") and $user->mobile) {
			?><tr><td></td><td><input type=checkbox name=sendsms value=1 class=input> <? print $_lang[TicketsSendSMS]?></td></tr><?
		}

		if (GetSetting("ticketsUsersCanAttach")) {
			?>
        		<tr><td colspan=2 align=center onclick="myShow('s1');" onmouseover="this.style.cursor='pointer'" class=<? print $font_head?>><B><? print $_lang[TicketsAttachFiles]?></b></td></tr>
			<tr><td colspan=2>
				<div id="s1" style="display: none;">
				<table width=100%>
				<tr><td><? print $_lang[TicketsFileNumber]?>1:</td><td><input type=file name=userfile[0]></td><td><? print $_lang[TicketsFileNumber]?>5:</td><td><input type=file name=userfile[4]></td></tr>
				<tr><td><? print $_lang[TicketsFileNumber]?>2:</td><td><input type=file name=userfile[1]></td><td><? print $_lang[TicketsFileNumber]?>6:</td><td><input type=file name=userfile[5]></td></tr>
				<tr><td><? print $_lang[TicketsFileNumber]?>3:</td><td><input type=file name=userfile[2]></td><td><? print $_lang[TicketsFileNumber]?>7:</td><td><input type=file name=userfile[6]></td></tr>
				<tr><td><? print $_lang[TicketsFileNumber]?>4:</td><td><input type=file name=userfile[3]></td><td><? print $_lang[TicketsFileNumber]?>8:</td><td><input type=file name=userfile[7]></td></tr>
				</table>
				</div>
			</td></tr>
			<?
		}
		?>
	        <tr><td colspan=2 align=center class=<? print $font_head?>><input class=button type=Submit value="<? print $_lang[TicketsAddTicket]?>"></td></tr></table><BR></form>
		<?
	}
	} else {
		print $_lang[TicketsDisabled];
	}

	foot('utf-8');	
}

if ($do == "changetarif") {
	$newTarif=@intval($newTarif);
	$host_id=@intval($host_id);

	if ($sub == "change") {
		if (!$newTarif) { $error = $_lang[ChangeErrorNoNewTarif]; }
		else if (!$host_id) { $error = $_lang[ErrorBadId]; }
		else if (!IsAccessibleChangeTarifForHostingOrder($host_id,$newTarif)) { $error = $_lang[ChangeErrorNoAccessTarif]; }
		else {
			$order = @mysql_query("select *,TO_DAYS(todate)-TO_DAYS(NOW()) as leftdays from orders where archived=0 and id='$host_id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			if (mysql_num_rows($order) > 0) {
				$order = mysql_fetch_object($order);

				$oldTarif = GetTarifById($order->tarif);
				$newTarif = GetTarifById($newTarif);

				if ($oldTarif->id and $newTarif->id) {
					if ($order->leftdays <= 0) {$order->leftdays = 0;}

					$newTarif->cost = $newTarif->cost / GetCurrencyKoeficientByCode($newTarif->costCurrency);
					$oldTarif->cost = $oldTarif->cost / GetCurrencyKoeficientByCode($oldTarif->costCurrency);

					$money = $order->leftdays * ($newTarif->cost - $oldTarif->cost)/30;
		               		$money = round($money, 2);
                			
               				if ($money > 0) {
						$history = "<B>$oldTarif->name</b> => <B>$newTarif->name</B>";

               					@mysql_query("insert into bills (uid,tarif,host_id,money_host,created,newtarif,history) values('$order->uid','$oldTarif->id','$order->id','$money',NOW(),'$newTarif->id','$history')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
						$billId = mysql_insert_id();
						mclose();
						Header("Location: billing.php?do=pay&fromchange=1&id=$billId");
						exit;
               				} else {
						if ($money < 0) {
							$money = -1 * $money;
							@mysql_query("update users set money=money+$money where id='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
						}

						$history = "<B>Тариф:</B> $newTarif->name";
						if ($order->domain) { $history .= ", <B>домен:</B> $order->domain";}

						if (changePackage($host_id, $newTarif->id)) {
							@mysql_query("update orders set tarif='$newTarif->id',history='$history' where id='$host_id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				               		addUserLog($_SESSION["userId"],'changetarif',"For order ID #$order->id, $order->domain, from $oldTarif->name to $newTarif->name");

							head('utf-8',$_lang[ChangeTitle]);
							print "<H1 class=pagetitle>".$_lang[ChangeTitle]."</H1><hr class=hr>";
							print $_lang[ChangeChangeTarifSuccess];
							foot('utf-8');

							mclose();
							exit;
						} else {
							@mysql_query("update orders set tarif='$newTarif->id',history='$history' where id='$host_id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				               		addUserLog($_SESSION["userId"],'changetarif',"For order ID #$order->id, $order->domain, from $oldTarif->name to $newTarif->name");

							$error = $_lang[ChangeErrorChange];
						}
					}
				}
			} else { $error = $_lang[ChangeErrorNoOrder]; }
		}
	}

	head('utf-8',$_lang[ChangeTitle]);
	print "<H1 class=pagetitle>".$_lang[ChangeTitle]."</H1><hr class=hr>";

	if ($error) {print "<font color=red>".$_lang[Error].": $error</font><BR><BR>";}

	if (!$host_id) { print "<font color=red>".$_lang[ErrorBadId]."</font><br><br>";}
	else {
		$accessibleTarifs = GetAccessibleChangeTarifsForHostingOrder($host_id);
		if ($accessibleTarifs) {
			$order = GetOrderById($host_id,$_SESSION["userId"]);
			$tarif = GetTarifById($order->tarif);

			if ($order->domain) { print "<B>".$_lang[ChangeDomain].":</b> $order->domain<Br>"; }
			print "<b>".$_lang[ChangeTarif].":</b> $tarif->name<Br><br>";

			print "<form method=post>";
			print "<input type=hidden name=do value=changetarif>";
			print "<input type=hidden name=sub value=change>";
			print "<input type=hidden name=host_id value=$host_id>";
			print "<b>".$_lang[ChangeNewTarif].":</b> <select class=input name=newTarif><option></option>";
			while ($rr = @mysql_fetch_object($accessibleTarifs)) {
#				if ($rr->cost_setup) {$addon_cost=" + ".round($rr->cost_setup*CURK,2)." ".CURS." ".$_lang[OrderRazovoZaUstanovku];} else {$addon_cost="";}

				$rr->cost = $rr->cost / GetCurrencyKoeficientByCode($rr->costCurrency);

				print "<option value=$rr->id>$rr->name (".round($rr->cost*CURK,2)." ".CURS."/".$_lang[OrderSokraschenieMonth].$addon_cost.")"."</option>";
			}
			print "</select>";
			print " <input class=button type=submit value='".$_lang[Save]."'>";
			print "</form>";
		}
	}

	foot('utf-8');
}

if ($do == "changeserver") {
	$newServer=@intval($newServer);
	$host_id=@intval($host_id);

	if ($sub == "change") {
		if (!$newServer) { $error = $_lang[ChangeErrorNoNewServer]; }
		else if (!$host_id) { $error = $_lang[ErrorBadId]; }
		else if (!IsAccessibleChangeServerForOrder($host_id,$newServer)) { $error = $_lang[ChangeErrorNoAccessServer]; }
		else {
			$order = @mysql_query("select *,TO_DAYS(todate)-TO_DAYS(NOW()) as leftdays from orders where archived=0 and id='$host_id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			if (mysql_num_rows($order) > 0) {
				$order = mysql_fetch_object($order);

				$tarif = GetTarifById($order->tarif);
				$tarif->cost = $tarif->cost / GetCurrencyKoeficientByCode($tarif->costCurrency);

				$oldServer = GetServers($order->serverid);
				$newServer = GetServers($newServer);

				if ($tarif->id and $oldServer->id and $newServer->id) {
					if ($order->leftdays <= 0) {$order->leftdays = 0;}

					$nacenka = $newServer->nacenka - $oldServer->nacenka;

					if ($nacenka > 0) {
						$money = $order->leftdays * (($tarif->cost/100*$nacenka)/30);
			               		$money = round($money, 2);
					} 
#					else if ($nacenka < 0) {
#						$money = $order->leftdays * (($tarif->cost/100*(-$nacenka))/30);
#			               		$money = round(-$money, 2);
#					}
                			
               				if ($money > 0) {
						$history = "<B>$tarif->name</b><BR><B>$oldServer->place</b> => <B>$newServer->place</B>";

               					@mysql_query("insert into bills (uid,tarif,server,host_id,money_host,created,newserver,history) values('$order->uid','$tarif->id','$oldServer->id','$order->id','$money',NOW(),'$newServer->id','".htmlEncode($history)."')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
						$billId = mysql_insert_id();
						mclose();
						Header("Location: billing.php?do=pay&fromchangeserver=1&id=$billId");
						exit;
               				} else {
#						if ($money < 0) {
#							$money = -$money;
#							@mysql_query("update users set money=money+$money where id='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
#						}

						if (changeServer($host_id, $newServer->id)) {
							@mysql_query("update orders set serverid='$newServer->id',changeServerCount=changeServerCount+1 where id='$host_id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				               		addUserLog($_SESSION["userId"],'changeserver',"For order ID #$order->id, $order->domain, $tarif->name, from $oldServer->name to $newServer->name");

							head('utf-8',$_lang[ChangeServerTitle]);
							print "<H1 class=pagetitle>".$_lang[ChangeServerTitle]."</H1><hr class=hr>";
							print $_lang[ChangeServerChangeServerSuccess];
							foot('utf-8');

							mclose();
							exit;
						} else {
							@mysql_query("update orders set serverid='$newServer->id',changeServerCount=changeServerCount+1 where id='$host_id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				               		addUserLog($_SESSION["userId"],'changeserver',"For order ID #$order->id, $order->domain, $tarif->name, from $oldServer->name to $newServer->name");

							$error = $_lang[ChangeServerErrorChange];
						}
					}
				}
			} else { $error = $_lang[ChangeErrorNoOrder]; }
		}
	}

	head('utf-8',$_lang[ChangeServerTitle]);
	print "<H1 class=pagetitle>".$_lang[ChangeServerTitle]."</H1><hr class=hr>";

	if ($error) {print "<font color=red>".$_lang[Error].": $error</font><BR><BR>";}

	if (!$host_id) { print "<font color=red>".$_lang[ErrorBadId]."</font><br><br>";}
	else {
		$accessibleServers = GetAccessibleChangeServersForOrder($host_id);
		if ($accessibleServers) {
			$order = GetOrderById($host_id,$_SESSION["userId"]);
			$tarif = GetTarifById($order->tarif);
			$tarif->cost = $tarif->cost / GetCurrencyKoeficientByCode($tarif->costCurrency);

			$server = GetServers($order->serverid);

			if ($tarif->changeServerLimit > 0 and ($tarif->changeServerLimit+$order->changeServerBuyed) <= $order->changeServerCount) { print "<font color=red>".$_lang[ChangeErrorLimit]."</font><br><br>"; }
			else {
				if ($order->domain) { print "<B>".$_lang[ChangeDomain].":</b> $order->domain<Br>"; }
				print "<b>".$_lang[ChangeTarif].":</b> $tarif->name<Br>";
				print "<b>".$_lang[ChangeServer].":</b> $server->place<Br><br>";

				print "<form method=post>";
				print "<input type=hidden name=do value=changeserver>";
				print "<input type=hidden name=sub value=change>";
				print "<input type=hidden name=host_id value=$host_id>";
				print "<b>".$_lang[ChangeNewServer].":</b> <select class=input name=newServer><option></option>";
				while ($rr = @mysql_fetch_object($accessibleServers)) {
					if ($order->leftdays <= 0) {$order->leftdays = 0;}

					$nacenka = $rr->nacenka - $server->nacenka;

					if ($nacenka > 0) {
						$money = $order->leftdays * (($tarif->cost/100*$nacenka)/30);
		               			$money = round($money, 2);
					} 
#					else if ($nacenka < 0) {
#						$money = $order->leftdays * (($tarif->cost/100*(-$nacenka))/30);
#		               			$money = round(-$money, 2);
#					}

					if ($money > 0) { $addon = $_lang[ChangeServerDoplata].": ".round($money*CURK,2)." ".CURS; } 
#					else if ($money < 0) { $addon = $_lang[ChangeServerBackMoney].": ".round(-$money*CURK,2)." ".CURS; }
					else { $addon = $_lang[ChangeServerDoplataWithout]; }

					print "<option value=$rr->id>$rr->place (".$addon.")"."</option>";
				}
				print "</select>";
				print " <input class=button type=submit value='".$_lang[Save]."'>";
				print "</form>";
			}
		}
	}

	foot('utf-8');
}

if ($do == "changeslots") {
	$host_id=@intval($host_id);
	$newSlots=@intval($newSlots);

	if ($sub == "change") {
		if (!$newSlots) { $error = $_lang[OrderErrorNoSlots]; }
		else if (!$host_id) { $error = $_lang[ErrorBadId]; }
		else {
			$order = GetOrderById($host_id,$_SESSION["userId"]);
			if ($order->id) {
				$tarif = GetTarifById($order->tarif);

				if ($tarif->id) {
					if ($tarif->minSlots and $newSlots < $tarif->minSlots) {$error=$_lang[OrderErrorMinSlots]." ".$tarif->minSlots;}
					else if ($tarif->maxSlots and $newSlots > $tarif->maxSlots) {$error=$_lang[OrderErrorMaxSlots]." ".$tarif->maxSlots;}
					else {
						$tarifSpecCost = GetSpecialCost($_SESSION['userId'],"tarif",$tarif->id);
						if ($tarifSpecCost) {
							$tarif->cost = $tarifSpecCost["cost2"];
						}
						else {
							$tarif->cost = $tarif->cost / GetCurrencyKoeficientByCode($tarif->costCurrency);
						}

						if ($server->nacenka) {
							$tarif->cost = $tarif->cost + $tarif->cost/100*$server->nacenka;
						}

						if ($order->leftdays <= 0) {$order->leftdays = 0;}

						$slotsRaznica = $newSlots - $order->slots;

						if ($slotsRaznica > 0) {
							$money = $order->leftdays * (($tarif->cost*$slotsRaznica)/30);
				               		$money = round($money, 2);
						}
#						else if ($slotsRaznica < 0) {
#							$money = $order->leftdays * (($tarif->cost*(-$slotsRaznica))/30);
#			               			$money = round(-$money, 2);
#						}
                			
	               				if ($money > 0) {
							$history = "<B>$tarif->name</b><BR><B>$order->slots</b> => <B>$newSlots</B>";

               						@mysql_query("insert into bills (uid,tarif,host_id,money_host,created,newslots,history) values('$order->uid','$tarif->id','$order->id','$money',NOW(),'$newSlots','".htmlEncode($history)."')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
							$billId = mysql_insert_id();
							mclose();
							Header("Location: billing.php?do=pay&fromchangeslots=1&id=$billId");
							exit;
	               				} else {
#							if ($money < 0) {
#								$money = -$money;
#								@mysql_query("update users set money=money+$money where id='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
#							}

							if (changeSlots($host_id, $newSlots)) {
								@mysql_query("update orders set slots='$newSlots' where id='$host_id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
					               		addUserLog($_SESSION["userId"],'changeslots',"For order ID #$order->id, $order->domain, $tarif->name, from $order->slots to $newSlots");

								head('utf-8',$_lang[ChangeSlotsTitle]);
								print "<H1 class=pagetitle>".$_lang[ChangeSlotsTitle]."</H1><hr class=hr>";
								print $_lang[ChangeSlotsChangeSuccess];
								foot('utf-8');

								mclose();
								exit;
							} else {
								@mysql_query("update orders set slots='$newSlots' where id='$host_id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
					               		addUserLog($_SESSION["userId"],'changeslots',"For order ID #$order->id, $order->domain, $tarif->name, from $order->slots to $newSlots");

								$error = $_lang[ChangeSlotsErrorChange];
							}
						}
					}
				}
			} else { $error = $_lang[ChangeErrorNoOrder]; }
		}
	}

	head('utf-8',$_lang[ChangeSlotsTitle]);
	print "<H1 class=pagetitle>".$_lang[ChangeSlotsTitle]."</H1><hr class=hr>";

	if ($error) {print "<font color=red>".$_lang[Error].": $error</font><BR><BR>";}

	if (!$host_id) { print "<font color=red>".$_lang[ErrorBadId]."</font><br><br>";}
	else {
		$order = GetOrderById($host_id,$_SESSION["userId"]);
		$tarif = GetTarifById($order->tarif);
		$server = GetServers($order->serverid);

		$tarifSpecCost = GetSpecialCost($_SESSION['userId'],"tarif",$tarif->id);
		if ($tarifSpecCost) {
			$tarif->cost = $tarifSpecCost["cost2"];
		}
		else {
			$tarif->cost = $tarif->cost / GetCurrencyKoeficientByCode($tarif->costCurrency);
		}

		if ($server->nacenka) {
			$tarif->cost = $tarif->cost + $tarif->cost/100*$server->nacenka;
		}

		$tarif->cost = round($tarif->cost*CURK,2)." ".CURS."/".$_lang[OrderSokraschenieMonth]." ".$_lang[OrderForSlot];

		if ($order->domain) { print "<B>".$_lang[ChangeDomain].":</b> $order->domain<Br>"; }
		print "<b>".$_lang[ChangeTarif].":</b> $tarif->name ($tarif->cost)<Br>";
		print "<b>".$_lang[ChangeSlots].":</b> $order->slots<Br><br>";

		print "<form method=post>";
		print "<input type=hidden name=do value=changeslots>";
		print "<input type=hidden name=sub value=change>";
		print "<input type=hidden name=host_id value=$host_id>";

		print "<b>".$_lang[ChangeNewSlots].":</b> <input type=text class=input name=newSlots size=2>";

		print " <input class=button type=submit value='".$_lang[Save]."'>";
		print "</form>";
	}

	foot('utf-8');
}

if ($do == "catalog") {
	head('utf-8',$_lang[CatTitle]);
	print "<H1 class=pagetitle>".$_lang[CatTitle]."</H1><hr class=hr>";

	if (!$sub) { $sub = "list"; }

	if ($sub == 'delete' and $id) {
		@mysql_query("delete from catalog where id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

		$file="./_rootimages/banners/cat_".$_SESSION["userId"]."_".$id.".";
		@unlink($file."gif");
		@unlink($file."jpg");
		@unlink($file."png");

		print $_lang[CatSiteDeleteSuccess]."<BR><BR>";
		$sub='list';
	}
	
	if ($sub == 'add2' or $sub == 'edit2') {
		if ($sub == 'add2') {$sub2='add';}
		if ($sub == 'edit2') {$sub2='edit';}

		$catalogImageEnable = GetSetting("catalog_image_enable");
		$catalogImageRequired = GetSetting("catalog_image_required");
		$catalogImageType = GetSetting("catalog_image_type");
		$maxWidth = GetSetting("catalog_image_maxwidth");
		$maxHeight = GetSetting("catalog_image_maxheight");

		if ($_FILES['userfile']['tmp_name']) {
			$imagesize = getimagesize($_FILES['userfile']['tmp_name']);
		}

		if (!$title) {$error = $_lang[CatErrorSite];}
		else if (!$url) {$error = $_lang[CatErrorDomain];}
		else if (preg_match("/\//ui",$url) or preg_match("/\:/ui",$url)) {$error = $_lang[CatErrorDomainSymbol];}
		else if (!$opisanie) {$error = $_lang[CatErrorOpisanie];}
		else if ($catalogImageEnable and $catalogImageRequired and !$_FILES['userfile']['tmp_name']) {$error = $_lang[CatSiteErrorNoImage];}
		else if ($catalogImageEnable and $_FILES['userfile']['tmp_name'] and $catalogImageType == "max" and (($imagesize[0] >= $maxWidth and $maxWidth != "0") or ($imagesize[1] >= $maxHeight and $maxHeight != "0"))) {$error = $_lang["CatSiteImageWrongSize"]." ".$maxWidth."x".$maxHeight;}
		else if ($catalogImageEnable and $_FILES['userfile']['tmp_name'] and $catalogImageType == "tochn" and (($imagesize[0] != $maxWidth and $maxWidth != "0") or ($imagesize[1] != $maxHeight and $maxHeight != "0"))) {$error = $_lang["CatSiteImageWrongTochnSize"]." ".$maxWidth."x".$maxHeight;}
		else {
			$siteip=gethostbyname($url);
			if ($siteip == $url and GetSetting("catalog_ip")) {$error = $_lang[CatErrorIP];}
			else if (!IsIPInIPs($siteip,GetSetting("catalog_ip"))) {$error = $_lang[CatSiteIP]." [$siteip] ".$_lang[CatIPDenied]."."; }
			else {
				if ($sub == 'add2') {
					@mysql_query("insert into catalog (uid,title,url,opisanie) values('".$_SESSION["userId"]."','$title','$url','$opisanie')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
					$id = mysql_insert_id();
					print $_lang[CatNewSiteSuccess]."<br><br>";
					$sub2='list';
				}
				else if ($sub == 'edit2') {
					if ($id) {
						@mysql_query("update catalog set title='$title',url='$url',opisanie='$opisanie' where id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
						print $_lang[CatSiteEditSuccess]."<br><br>";
						$sub2='list';
					}
					else {
						print $_lang[ErrorBadId]."<br><br>";
						$sub2='list';
					}
				}

				if ($catalogImageEnable and $id and $_FILES['userfile']['name'] != '' and $_FILES['userfile']['type'] != '' and $_FILES['userfile']['tmp_name'] != '') {
					$type = getFileType($_FILES['userfile']['tmp_name']);

					if ($type == "image/jpeg" or $type == "image/gif" or $type == "image/png") {
						$ext = mb_split('/',$type); $ext = $ext[1];
						$file="./_rootimages/banners/cat_".$_SESSION["userId"]."_".$id.".";

						@unlink($file."gif");
						@unlink($file."jpg");
						@unlink($file."png");

						if (move_uploaded_file($_FILES['userfile']['tmp_name'],$file.$ext)) {
							@chmod($file.$ext, 0777);
						}
						else {
							print $_lang["CatSiteImageCantMove"]."<br><BR>";
						}
					}
					else {
						print $_lang["CatSiteImageWrongType"]."<br><BR>";
					}
				}
			}
		}

		$sub=$sub2;
	}
        
	if ($sub == 'add' or $sub == 'edit') {
		if ($error) { print "<font color=red>".$_lang[Error].": $error</font><br><br>"; }

		$button=$_lang[Add];
		$text = $_lang[CatAdding];
		if ($sub == 'edit') {
			$r=@mysql_query("select * from catalog where id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			$r=mysql_fetch_object($r);
			$title=$r->title;
			$url=$r->url;
			$opisanie=$r->opisanie;

			$button=$_lang[Change];
			$text=$_lang[CatEditing];
		}

		?>
		<form method=post enctype="multipart/form-data">
		<input type=hidden name=do value=<? print $do?>>
		<input type=hidden name=sub value=<? print $sub?>2>
		<input type=hidden name=id value=<? print $id?>>
		<table class='rpTable'>
		<tr><td colspan=2 align=center bgcolor=#EAEAEA><B><? print $text?> <? print $_lang[CatSaita]?></b></td></tr>
		<tr><td><? print $_lang[CatSiteName]?>:</td><td><input class=input type=text size=53 name=title maxlength=75 value="<? print $title?>"></td></tr>
		<tr><td><? print $_lang[CatSiteDomain]?> (<? print $_lang[CatWithoutHttp]?>):</td><td><input class=input type=text size=53 name=url maxlength=100 value="<? print $url?>"></td></tr>
		<tr><td><? print $_lang[CatSiteOpisanie]?>:</td><td><input class=input type=text size=53 name=opisanie maxlength=250 value="<? print $opisanie?>"></td></tr>
		<? if (GetSetting("catalog_image_enable")) { ?>
			<tr><td valign=top><? print $_lang[CatSiteImage]?>:</td><td><input type='file' name='userfile'><BR><? print $_lang[CatSiteImageMaxSize]?>: <? print GetSetting("catalog_image_maxwidth")?>x<? print GetSetting("catalog_image_maxheight")?></td></tr>
		<? } ?>
		<tr><Td colspan=2 align=center><BR><input class=button type=submit value="<? print $button?>"></td></tr>
		</table>
		</form>
		<?
	}

	
	if ($sub == "list") {
		$r=@mysql_query("select * from catalog where uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		?>
		<table class='rpTable' cellpadding=3>
		<tr><td colspan=4 align=center class=<? print $font_head?>><B><? print $_lang[CatYourSites]?></b></td></tr>
		<tr class=<? print $font_head?>><td></td><td align=center><? print $_lang[CatSiteName]?></td><td align=center><? print $_lang[CatSiteDomain]?></td><td align=center></td></tr>
		<?
		$cnt=0;
		while ($rr = @mysql_fetch_object($r)) {
			getfont();

			$file="./_rootimages/banners/cat_".$_SESSION["userId"]."_".$rr->id.".";
			if (file_exists($file."gif")) { $file = $file."gif"; }
			else if (file_exists($file."jpg")) { $file = $file."jpg"; }
			else if (file_exists($file."png")) { $file = $file."png"; }
			else { $file = ""; }
	
			if ($file) { $file = "<img src='$file'>"; }

			print "
			<tr class=$font_row>
			<td>$file</td>
			<td>$rr->title</td>
			<td>$rr->url</td>
			<Td align=center><A class=rootlink href=?do=$do&sub=edit&id=$rr->id><img src=./_rootimages/edit.gif alt='".$_lang[CatChange]."' border=0></a><A class=rootlink href=?do=$do&sub=delete&id=$rr->id onclick=\"javascript: return confirm('".$_lang[CatDeleteAlert]."');\"><img src=./_rootimages/del.gif alt='".$_lang[CatDelete]."' border=0></a></td>
			</tr>
			";
	
			$cnt++;
		}
		?>
		<tr class=<? print $font_head?>><Td colspan=4><? print $_lang[CatTotalSites]?>: <? print $cnt?></td></tr>
		<tr><td align=right colspan=4><A class=rootlink href=?do=<? print $do?>&sub=add><? print $_lang[CatAddSite]?></a></td></tr>
		</table>
		<?
	}
	foot('utf-8');
}

if ($do == "addfunds") {
	$money=floatval($money); $money=round($money/CURK,2);

	if ($sub == "pay") {
		if ($workWithoutAuth) {
			if (!$ident) { $error = $_lang[FundErrorNoLoginOrId]; }
			else {
				$user = GetUserByLoginOrId($ident);
				if ($user->id) {
					$sqlUserId = $user->id;
					$sqlUserLogin = $user->login;
				}
				else { $error = $_lang[FundErrorNoUser]; }
			}
		} else {
			$sqlUserId = $_SESSION["userId"];
			$sqlUserLogin = $_SESSION["userLogin"];
		}

		if (!$error) {
			if ($money < 1) { $error = $_lang[FundsMinSumm]." ".round(1*CURK,2)." ".CURS; }
			else {
				@mysql_query("insert into bills (uid,money,created,addfunds,history) values('".$sqlUserId."','$money',NOW(),'1','".$sqlUserLogin."')") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
				$bill_id = mysql_insert_id();

				Header("Location: billing.php?do=pay&id=$bill_id");exit;
			}
		}
	}

	if ($sub == "coupon" and $coupon and !$workWithoutAuth) {
		$coupon = GetCoupon("coupon",$coupon,'','','');

		if (!$coupon->id) { $error = $_lang[FundsErrorCoupon]; }
		else if (!IsCanUseCoupon($coupon->id,$_SESSION["userId"])) { $error = $_lang[FundsErrorCoupon]; }
		else {
			@mysql_query("update users set money=money+$coupon->value where id='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			AddUsedByToCoupon($coupon->id,$_SESSION["userId"]);
			addUserLog($_SESSION['userId'], "moneyIn", $_lang[FundsTitle].", ".round($coupon->value*CURK,2)." ".CURS.", coupon $coupon->code");
			$success = $_lang[FundsCouponSuccess];
		}
	}

	head('utf-8',$_lang[FundsTitle]);

	print "<H1 class=pagetitle>".$_lang[FundsTitle]."</H1><hr class=hr>";

	if ($error) {print "<font color=red>".$_lang[Error].": $error</font><BR><BR>";}
	if ($success) { print "$success ".round($coupon->value*CURK,2)." ".CURS."<BR><BR>"; }

	print "<form method=post>";
	print "<input type=hidden name=do value=addfunds>";
	print "<input type=hidden name=sub value=pay>";
	if ($workWithoutAuth) { print $_lang["FundsLoginOrId"].": <input class=input type=text name=ident value='$ident'><BR>";  }
	print $_lang[FundsSumm].": <input class=input type=text name=money value=0.00 size=4> ".CURS.""; 
	print " <input class=button type=submit value='".$_lang[FundsGotoPay]."'>";
	print "</form>";

	if (!$workWithoutAuth) {
		print "<form method=post>";
		print "<input type=hidden name=do value=addfunds>";
		print "<input type=hidden name=sub value=coupon>";
		print $_lang[FundsCouponCode].": <input class=input type=text name=coupon size=15>"; 
		print " <input class=button type=submit value='".$_lang[FundsCouponPay]."'>";
		print "</form>";
	}

	foot('utf-8');
}

if ($do == "maillogs") {
	head('utf-8',$_lang[MailLogsTitle]);
	print "<H1 class=pagetitle>".$_lang[MailLogsTitle]."</H1><hr class=hr>";

	if ($sub == "delete" and $id and GetSetting('mailLogUserDelete')) {
		$r=@mysql_query("select * from mail_logs where id='$id' and uid=".$_SESSION["userId"]) or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
		if (mysql_num_rows($r) > 0) {
			@mysql_query("delete from mail_logs where id='$id'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
			print $_lang[MailLogsDeleteSuccess]."<br><br>";
		}
		$sub = "";
	}

	if ($sub == "view") {
		if (!$id) {print "<font color=red>".$_lang[ErrorBadId]."</font><br><br>";}
		else {
			$r=@mysql_query("select * from mail_logs where id='$id' and uid='".$_SESSION["userId"]."'") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

			if (mysql_num_rows($r) == 0) {print "<font color=red>".$_lang[MailLogsErrorNoMail]."</font><br><br>";}
			else {
				$rr = mysql_fetch_object($r);

				$mailLogUserDelete = GetSetting('mailLogUserDelete');

				print "<table class='rpTable' cellpadding=3>";

				$rr->subject = htmlDecode($rr->subject);
				print "<tr><td align=center class=$font_head>".$_lang[MailLogsSubject].": <B>".$rr->subject."</b></td></tr>";

				$rr->message = preg_replace("/\r\n/ui", "<BR>", htmlDecode($rr->message));
				$rr->message = preg_replace("/\n/ui", "<BR>", htmlDecode($rr->message));
				$rr->message = preg_replace("/&/ui", "&amp;", htmlDecode($rr->message));

				if ($mailLogUserDelete) {
					$delete="<A class=rootlink href=?do=$do&sub=delete&id=$rr->id onclick=\"javascript: return confirm('".$_lang[MailLogsDeleteAlert]."');\"><img src=./_rootimages/del.gif border=0 alt='".$_lang[MailLogsDelete]."'>".$_lang[MailLogsDelete]."</a>";
				} else {
					$delete="";
				}

				print "<tr><td class=$font_row2>".$_lang[MailLogsDate].": $rr->dt</td></tr>";
				print "<tr class=$font_row1><td valign=top>$rr->message<Br><br></td></tr>";
				print "<tr class=$font_row1 align=center><td valign=top>$delete</td></tr>";

				print "</table>";
			}
		}
	}

	if (!$sub) {
	        $r=@mysql_query("select * from mail_logs where uid='".$_SESSION["userId"]."' order by id desc") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());
        	$rows = mysql_num_rows($r);
	        list($start, $perPage, $txt) = MakePages($page, $rows);

	        ?>
	        <table class='rpTable' cellpadding=3>
	        <tr><td colspan=4 align=right><? print $txt?></td></tr>
	        <tr class=<? print $font_head?> align=center><td>#</td><td><? print $_lang[MailLogsDate]?></td><td><? print $_lang[MailLogsSubject]?></td><td></td></tr>
	        <?

	        $r=@mysql_query("select * from mail_logs where uid='".$_SESSION["userId"]."' order by id desc LIMIT $start,$perPage") or die("File: ".__FILE__."<BR>Line: ".__LINE__."<BR>MySQL Error: ".mysql_error());

		$mailLogUserDelete = GetSetting('mailLogUserDelete');

	        $cnt=0;
	        while ($rr = @mysql_fetch_object($r)) {
			getfont();
			$cnt++;

			if ($mailLogUserDelete) {
				$delete="<A class=rootlink href=?do=$do&sub=delete&id=$rr->id onclick=\"javascript: return confirm('".$_lang[MailLogsDeleteAlert]."');\"><img src=./_rootimages/del.gif border=0 alt='".$_lang[MailLogsDelete]."'></a>";
			} else {
				$delete="";
			}

			$rr->subject = htmlDecode($rr->subject);

			?>
			<tr class="<? print $font_row?>" height=30>
			<td valign=middle>&nbsp;<? print $cnt?>&nbsp;</td>
			<td>&nbsp;<? print $rr->dt?>&nbsp;</td>
			<td>&nbsp;<a class=rootlink href=?do=<? print $do?>&sub=view&id=<? print $rr->id?>><? print $rr->subject?></a>&nbsp;</td>
			<td align=center><? print $delete?></td>
			</tr>
			<?
		}
		?>
	        <tr class=<? print $font_head?>><Td colspan=4><? print $_lang[MailLogsTotalMail]?>: <? print $rows?>, <? print $_lang[MailLogsOnPage]?>: <? print $cnt?></td></tr>
	        <tr><td colspan=4 align=right><? print $txt?></td></tr>
		</table><br>

		<?
	}

	foot('utf-8');	
}

mclose();
?>