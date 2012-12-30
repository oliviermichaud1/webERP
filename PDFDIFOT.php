<?php

/* $Id$*/

include ('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');

$InputError=0;

if (isset($_POST['FromDate']) and !Is_Date($_POST['FromDate'])){
	$msg = _('The date from must be specified in the format') . ' ' . $_SESSION['DefaultDateFormat'];
	$InputError=1;
}
if (isset($_POST['ToDate']) and !Is_Date($_POST['ToDate'])){
	$msg =  _('The date to must be specified in the format') . ' ' .  $_SESSION['DefaultDateFormat'];
	$InputError=1;
}

if (!isset($_POST['FromDate']) or !isset($_POST['ToDate']) or $InputError==1){

	 $Title = _('Delivery In Full On Time (DIFOT) Report');
	 include ('includes/header.inc');

	echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/transactions.png" title="' . $Title . '" alt="" />' . ' '
		. _('DIFOT Report') . '</p>';

	 echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
     echo '<div>';
	 echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	 echo '<table class="selection">
			<tr>
				<td>' . _('Enter the date from which variances between orders and deliveries are to be listed') . ':</td>
				<td><input type="text" class="date" alt="' .$_SESSION['DefaultDateFormat'].'" name="FromDate" maxlength="10" size="10" value="' . Date($_SESSION['DefaultDateFormat'], Mktime(0,0,0,Date('m')-1,0,Date('y'))) . '" /></td>
			</tr>';
	 echo '<tr>
			<td>' . _('Enter the date to which variances between orders and deliveries are to be listed') . ':</td>
			<td><input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="ToDate" maxlength="10" size="10" value="' . Date($_SESSION['DefaultDateFormat']) . '" /></td>
		</tr>';

	 if (!isset($_POST['DaysAcceptable'])){
		$_POST['DaysAcceptable'] = 1;
	 }

	 echo '<tr>
				<td>' . _('Enter the number of days considered acceptable between delivery requested date and invoice date(ie the date dispatched)') . ':</td>
				<td><input type="text" class="number" name="DaysAcceptable" maxlength="2" size="2" value="' . $_POST['DaysAcceptable'] . '" /></td>
			</tr>';
	 echo '<tr><td>' . _('Inventory Category') . '</td><td>';

	 $sql = "SELECT categorydescription, categoryid FROM stockcategory WHERE stocktype<>'D' AND stocktype<>'L'";
	 $result = DB_query($sql,$db);


	 echo '<select name="CategoryID">';
	 echo '<option selected="selected" value="All">' . _('Over All Categories') . '</option>';

	while ($myrow=DB_fetch_array($result)){
		echo '<option value="' . $myrow['categoryid'] . '">' . $myrow['categorydescription'] . '</option>';
	}

	 echo '</select></td></tr>';

	 echo '<tr><td>' . _('Inventory Location') . ':</td>
			<td><select name="Location">';
	 echo '<option selected="selected" value="All">' . _('All Locations') . '</option>';

	$result= DB_query("SELECT loccode, locationname FROM locations",$db);
	while ($myrow=DB_fetch_array($result)){
		echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
	}
	 echo '</select></td></tr>';

	 echo '<tr><td>' . _('Email the report off') . ':</td>
			<td><select name="Email">
				<option selected="selected" value="No">' . _('No') . '</option>
				<option value="Yes">' . _('Yes') . '</option>
			</select></td>
		</tr>
		</table>
		<br />
		<div class="centre">
		<input type="submit" name="Go" value="' . _('Create PDF') . '" />
		</div>';
     echo '</div>
           </form>';

	 if ($InputError==1){
	 	prnMsg($msg,'error');
	 }
	 include('includes/footer.inc');
	 exit;
} else {
	 include('includes/ConnectDB.inc');
}

if ($_POST['CategoryID']=='All' and $_POST['Location']=='All'){
	$sql= "SELECT salesorders.orderno,
				salesorders.deliverydate,
				salesorderdetails.actualdispatchdate,
				TO_DAYS(salesorderdetails.actualdispatchdate) - TO_DAYS(salesorders.deliverydate) AS daydiff,
				salesorderdetails.quantity,
				salesorderdetails.stkcode,
				stockmaster.description,
				stockmaster.decimalplaces,
				salesorders.debtorno,
				salesorders.branchcode
			FROM salesorderdetails INNER JOIN stockmaster
			ON salesorderdetails.stkcode=stockmaster.stockid
			INNER JOIN salesorders ON salesorderdetails.orderno=salesorders.orderno
			WHERE salesorders.deliverydate >='" . FormatDateForSQL($_POST['FromDate']) . "'
			AND salesorders.deliverydate <='" . FormatDateForSQL($_POST['ToDate']) . "'
			AND (TO_DAYS(salesorderdetails.actualdispatchdate) - TO_DAYS(salesorders.deliverydate)) >'" . filter_number_format($_POST['DaysAcceptable']) ."'";

} elseif ($_POST['CategoryID']!='All' and $_POST['Location']=='All') {
				$sql= "SELECT salesorders.orderno,
							salesorders.deliverydate,
							salesorderdetails.actualdispatchdate,
							TO_DAYS(salesorderdetails.actualdispatchdate) - TO_DAYS(salesorders.deliverydate) AS daydiff,
							salesorderdetails.quantity,
							salesorderdetails.stkcode,
							stockmaster.description,
							stockmaster.decimalplaces,
							salesorders.debtorno,
							salesorders.branchcode
						FROM salesorderdetails INNER JOIN stockmaster
						ON salesorderdetails.stkcode=stockmaster.stockid
						INNER JOIN salesorders ON salesorderdetails.orderno=salesorders.orderno
						WHERE salesorders.deliverydate >='" . FormatDateForSQL($_POST['FromDate']) . "'
						AND salesorders.deliverydate <='" . FormatDateForSQL($_POST['ToDate']) . "'
						AND stockmaster.categoryid='" . $_POST['CategoryID'] ."'
						AND (TO_DAYS(salesorderdetails.actualdispatchdate)
							- TO_DAYS(salesorders.deliverydate)) >'" . filter_number_format($_POST['DaysAcceptable'])."'";

} elseif ($_POST['CategoryID']=='All' and $_POST['Location']!='All') {

				$sql= "SELECT salesorders.orderno,
							salesorders.deliverydate,
							salesorderdetails.actualdispatchdate,
							TO_DAYS(salesorderdetails.actualdispatchdate) - TO_DAYS(salesorders.deliverydate) AS daydiff,
							salesorderdetails.quantity,
							salesorderdetails.stkcode,
							stockmaster.description,
							stockmaster.decimalplaces,
							salesorders.debtorno,
							salesorders.branchcode
						FROM salesorderdetails INNER JOIN stockmaster
						ON salesorderdetails.stkcode=stockmaster.stockid
						INNER JOIN salesorders ON salesorderdetails.orderno=salesorders.orderno
						WHERE salesorders.deliverydate >='" . FormatDateForSQL($_POST['FromDate']) . "'
						AND salesorders.deliverydate <='" . FormatDateForSQL($_POST['ToDate']) . "'
						AND salesorders.fromstkloc='" . $_POST['Location'] . "'
						AND (TO_DAYS(salesorderdetails.actualdispatchdate)
								- TO_DAYS(salesorders.deliverydate)) >'" . filter_number_format($_POST['DaysAcceptable']) . "'";

} elseif ($_POST['CategoryID']!='All' and $_POST['Location']!='All'){

				$sql= "SELECT salesorders.orderno,
							salesorders.deliverydate,
							salesorderdetails.actualdispatchdate,
							TO_DAYS(salesorderdetails.actualdispatchdate) - TO_DAYS(salesorders.deliverydate) AS daydiff,
							salesorderdetails.quantity,
							salesorderdetails.stkcode,
							stockmaster.description,
							stockmaster.decimalplaces,
							salesorders.debtorno,
							salesorders.branchcode
						FROM salesorderdetails INNER JOIN stockmaster
						ON salesorderdetails.stkcode=stockmaster.stockid
						INNER JOIN salesorders ON salesorderdetails.orderno=salesorders.orderno
						WHERE salesorders.deliverydate >='" . FormatDateForSQL($_POST['FromDate']) . "'
						AND salesorders.deliverydate <='" . FormatDateForSQL($_POST['ToDate']) . "'
						AND stockmaster.categoryid='" . $_POST['CategoryID'] ."'
						AND salesorders.fromstkloc='" . $_POST['Location'] . "'
						AND (TO_DAYS(salesorderdetails.actualdispatchdate)
								- TO_DAYS(salesorders.deliverydate)) >'" . filter_number_format($_POST['DaysAcceptable']) . "'";

}

$Result=DB_query($sql,$db,'','',false,false); //dont error check - see below

if (DB_error_no($db)!=0){
	$Title = _('DIFOT Report Error');
	include('includes/header.inc');
	prnMsg( _('An error occurred getting the days between delivery requested and actual invoice'),'error');
	if ($debug==1){
		prnMsg( _('The SQL used to get the days between requested delivery and actual invoice dates was') . "<br />$sql",'error');
	}
	include ('includes/footer.inc');
	exit;
} elseif (DB_num_rows($Result) == 0){
	$Title = _('DIFOT Report Error');
  	include('includes/header.inc');
	prnMsg( _('There were no variances between deliveries and orders found in the database within the period from') . ' ' . $_POST['FromDate'] . ' ' . _('to') . ' ' . $_POST['ToDate'] . '. ' . _('Please try again selecting a different date range'), 'info');
	if ($debug==1) {
		prnMsg( _('The SQL that returned no rows was') . '<br />' . $sql,'error');
	}
	include('includes/footer.inc');
	exit;
}

include('includes/PDFStarter.php');

/*PDFStarter.php has all the variables for page size and width set up depending on the users default preferences for paper size */

$pdf->addInfo('Title',_('Dispatches After') . $_POST['DaysAcceptable'] . ' ' . _('Day(s) from Requested Delivery Date'));
$pdf->addInfo('Subject',_('Delivery Dates from') . ' ' . $_POST['FromDate'] . ' ' . _('to') . ' ' . $_POST['ToDate']);
$line_height=12;
$PageNumber = 1;
$TotalDiffs = 0;

include ('includes/PDFDIFOTPageHeader.inc');

while ($myrow=DB_fetch_array($Result)){

	  if (DayOfWeekFromSQLDate($myrow['actualdispatchdate'])==1){
		 $DaysDiff = $myrow['daydiff']-2;
	  } else {
		 $DaysDiff = $myrow['daydiff'];
	  }
	  if ($DaysDiff > $_POST['DaysAcceptable']){
			$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,40,$FontSize,$myrow['orderno'], 'left');
			$LeftOvers = $pdf->addTextWrap($Left_Margin+40,$YPos,200,$FontSize,$myrow['stkcode'] .' - ' . $myrow['description'], 'left');
			$LeftOvers = $pdf->addTextWrap($Left_Margin+240,$YPos,50,$FontSize,locale_number_format($myrow['quantity'],$myrow['decimalplaces']), 'right');
			$LeftOvers = $pdf->addTextWrap($Left_Margin+295,$YPos,50,$FontSize,$myrow['debtorno'], 'left');
			$LeftOvers = $pdf->addTextWrap($Left_Margin+345,$YPos,50,$FontSize,$myrow['branchcode'], 'left');
			$LeftOvers = $pdf->addTextWrap($Left_Margin+395,$YPos,50,$FontSize,ConvertSQLDate($myrow['actualdispatchdate']), 'left');
			$LeftOvers = $pdf->addTextWrap($Left_Margin+445,$YPos,20,$FontSize,$DaysDiff, 'left');

			$YPos -= ($line_height);
			$TotalDiffs++;

			if ($YPos - (2 *$line_height) < $Bottom_Margin){
		  /*Then set up a new page */
			  $PageNumber++;
		  include ('includes/PDFDIFOTPageHeader.inc');
			} /*end of new page header  */
	  }
} /* end of while there are delivery differences to print */


$YPos-=$line_height;
$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,200,$FontSize,_('Total number of differences') . ' ' . locale_number_format($TotalDiffs), 'left');

if ($_POST['CategoryID']=='All' and $_POST['Location']=='All'){
	$sql = "SELECT COUNT(salesorderdetails.orderno)
			FROM salesorderdetails INNER JOIN debtortrans
				ON salesorderdetails.orderno=debtortrans.order_
			WHERE debtortrans.trandate>='" . FormatDateForSQL($_POST['FromDate']) . "'
			AND debtortrans.trandate <='" . FormatDateForSQL($_POST['ToDate']) . "'";

} elseif ($_POST['CategoryID']!='All' and $_POST['Location']=='All') {
	$sql = "SELECT COUNT(salesorderdetails.orderno)
		FROM salesorderdetails INNER JOIN debtortrans
			ON salesorderdetails.orderno=debtortrans.order_ INNER JOIN stockmaster
			ON salesorderdetails.stkcode=stockmaster.stockid
		WHERE debtortrans.trandate>='" . FormatDateForSQL($_POST['FromDate']) . "'
		AND debtortrans.trandate <='" . FormatDateForSQL($_POST['ToDate']) . "'
		AND stockmaster.categoryid='" . $_POST['CategoryID'] . "'";

} elseif ($_POST['CategoryID']=='All' and $_POST['Location']!='All'){

	$sql = "SELECT COUNT(salesorderdetails.orderno)
		FROM salesorderdetails INNER JOIN debtortrans
			ON salesorderdetails.orderno=debtortrans.order_ INNER JOIN salesorders
			ON salesorderdetails.orderno = salesorders.orderno
		WHERE debtortrans.trandate>='". FormatDateForSQL($_POST['FromDate']) . "'
		AND debtortrans.trandate <='" . FormatDateForSQL($_POST['ToDate']) . "'
		AND salesorders.fromstkloc='" . $_POST['Location'] . "'";

} elseif ($_POST['CategoryID'] !='All' and $_POST['Location'] !='All'){

	$sql = "SELECT COUNT(salesorderdetails.orderno)
		FROM salesorderdetails INNER JOIN debtortrans ON salesorderdetails.orderno=debtortrans.order_
			INNER JOIN salesorders ON salesorderdetails.orderno = salesorders.orderno
			INNER JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
		WHERE salesorders.fromstkloc ='" . $_POST['Location'] . "'
		AND categoryid='" . $_POST['CategoryID'] . "'
		AND trandate >='" . FormatDateForSQL($_POST['FromDate']) . "'
		AND trandate <= '" . FormatDateForSQL($_POST['ToDate']) . "'";

}
$ErrMsg = _('Could not retrieve the count of sales order lines in the period under review');
$result = DB_query($sql,$db,$ErrMsg);


$myrow=DB_fetch_row($result);
$YPos-=$line_height;
$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,200,$FontSize,_('Total number of order lines') . ' ' . locale_number_format($myrow[0]), 'left');

$YPos-=$line_height;
$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,200,$FontSize,_('DIFOT') . ' ' . locale_number_format((1-($TotalDiffs/$myrow[0])) * 100,2) . '%', 'left');


$ReportFileName = $_SESSION['DatabaseName'] . '_DIFOT_' . date('Y-m-d').'.pdf';
$pdf->OutputD($ReportFileName);
$pdf->__destruct();

if ($_POST['Email']=='Yes'){
	if (file_exists($_SESSION['reports_dir'] . '/'.$ReportFileName)){
		unlink($_SESSION['reports_dir'] . '/'.$ReportFileName);
	}
	$fp = fopen( $_SESSION['reports_dir'] . '/'.$ReportFileName,'wb');
	fwrite ($fp, $pdfcode);
	fclose ($fp);

	include('includes/htmlMimeMail.php');

	$mail = new htmlMimeMail();
	$attachment = $mail->getFile($_SESSION['reports_dir'] . '/'.$ReportFileName);
	$mail->setText(_('Please find herewith DIFOT report from') . ' ' . $_POST['FromDate'] .  ' '. _('to') . ' ' . $_POST['ToDate']);
	$mail->addAttachment($attachment, 'DIFOT.pdf', 'application/pdf');
	$mail->setFrom($_SESSION['CompanyRecord']['coyname'] . '<' . $_SESSION['CompanyRecord']['email'] .'>');

	/* $DelDiffsRecipients defined in config.php */
	$result = $mail->send($DelDiffsRecipients);
}

?>