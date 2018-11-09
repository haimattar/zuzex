<?php
@session_start();
		//echo "<pre>";
		//print_r($_SERVER);
		 $WHERE = "";
		global $wpdb;
		date_default_timezone_set("Europe/Paris");
		$table_prefix = $wpdb->prefix;
		$user_id = get_current_user_id();
		$user = new WP_User( $user_id );
		foreach( $user->roles as $role ) {
			 $role = get_role( $role );
			 if ( $role != null )
			  $roles = $role->name;
		}
		if($roles=="professionaluser"){
			$parent_userid = get_user_meta($user_id,"wp_user_parent",true);
		}else if($roles=="admin"){
			$parent_userid = get_user_meta($user_id,"wp_user_parent",true);
			if(empty($parent_userid)){
				$parent_userid = $user_id;
			}
		}
		include("simulator_opt.php");
		$search_status = isset($_POST['status'])?$_POST['status']:"Classique";
		$keywords = isset($_POST['keywords'])?$_POST['keywords']:"";
		if(!empty($keywords)){
				$WHERE .= "AND (file_number LIKE '%".esc_sql($keywords)."%' OR '".$keywords."'='')
				OR (pusalesname LIKE '%".esc_sql($keywords)."%' OR '".$keywords."'='')
				OR (pucompany_name LIKE '%".esc_sql($keywords)."%' OR '".$keywords."'='')
				OR (pusociety_number LIKE '%".esc_sql($keywords)."%' OR '".$keywords."'='')
				OR (pufirst_name LIKE '%".esc_sql($keywords)."%' OR '".$keywords."'='')
				OR (pusociety_postcode LIKE '%".esc_sql($keywords)."%' OR '".$keywords."'='')
				OR (pucity LIKE '%".esc_sql($keywords)."%' OR '".$keywords."'='')
				OR (name LIKE '%".esc_sql($keywords)."%' OR '".$keywords."'='')
				OR (ville LIKE '%".esc_sql($keywords)."%' OR '".$keywords."'='')
				OR (client_society_name LIKE '%".esc_sql($keywords)."%' OR '".$keywords."'='')
				OR (de_siren LIKE '%".esc_sql($keywords)."%' OR '".$keywords."'='')
				OR ((fiche LIKE '%".esc_sql($keywords)."%' OR REPLACE ( REPLACE ( fiche, '-', '' ) , ' ', '' ) LIKE '".esc_sql($keywords)."%') OR '".$keywords."'='')";
			}
			if($roles=="admin"){
				$WHERE .= "AND (userid='".$user_id."'
						   AND parent_userid='".$parent_userid."')
						   OR parent_userid='".$user_id."'";
			}else if($roles=="professionaluser"){
				$WHERE .= "AND userid='".$user_id."'
						   AND parent_userid='".$parent_userid."'";
			}else if($roles=="superadmin"){
				$WHERE .= "AND usertype!='administrator'";
			}
			$SQL = "SELECT * FROM  wp_clientinfo
					WHERE id!=''
					".$WHERE."
					ORDER BY id DESC";
			$rs = $wpdb->get_results($SQL);
			$field_name = isset($_POST['field_name'])?$_POST['field_name']:"";
			$field_value = isset($_POST['field_value'])?$_POST['field_value']:"";
			//===Columns Sum Query====//
			$SQLTOTALS = "SELECT SUM(class_prime) as TOTALCLASSPRIME,SUM(class_bonus) AS TOTALCLASSBONUS,SUM(prec_bonus) AS TOTALPRECBONUS,SUM(profits) AS TOTALPROFITS
						 FROM  wp_clientinfo
						 WHERE id!=''
						 ".$WHERE."";
			$rsTotals = $wpdb->get_results($SQLTOTALS);
			//print_r($rsTotals);
			//===Columns Sum Query====//
			//Multiple delete
	 if(isset($_POST['delid']) && !empty($_POST['delid'])){
		foreach($_POST['delid'] as $post_id){
			$SQL = "SELECT * FROM  wp_clientinfo WHERE id='".$post_id."'";
			$rs = $wpdb->get_results($SQL);
			$project_meta_id = $rs[0]->pmetaid;
			$filenos = $rs[0]->file_number;
			$document8 = $rs[0]->document8;
			$document9 = $rs[0]->document9;
			$document10 = $rs[0]->document10;
			$document11 = $rs[0]->document11;
			$directory = ABSPATH.'wp-content/plugins/crm_new/files/'.$filenos;			
			if(!empty($project_meta_id)){
			$SQLDELETEMETACONDS = "DELETE FROM wp_project_meta_conditions WHERE project_meta_id='".$project_meta_id."'";
			$wpdb->query($SQLDELETEMETACONDS);

			$SQLDELETEMETA = "DELETE FROM wp_project_meta_wording WHERE project_meta_id='".$project_meta_id."'";
			$wpdb->query($SQLDELETEMETA);

			$SQLDELETE = "DELETE FROM wp_project_meta WHERE id='".$project_meta_id."'";
			$wpdb->query($SQLDELETE);
			
			$SQLSUBCONDSDELETE = "DELETE FROM wp_project_meta_subconditions  WHERE project_meta_id='".$project_meta_id."'";
			$wpdb->query($SQLSUBCONDSDELETE);	
		 }
		
		  $SQLDEL = "DELETE FROM wp_clientinfo WHERE id ='".$post_id."'";
		  $wpdb->query($SQLDEL);		 	
		}
		$_SESSION['SuccessMsg'] = "Record Deleted Successfully.....";	 
		wp_redirect($siteurl."/wp-admin/admin.php?page=crm_new");	
		exit; 
	 }
	 
	 //Multiple delete
	 //==Update Docusign API==//
	function update_docusignapis($Docs) {
		global $wpdb;
		date_default_timezone_set("Europe/Paris");
		$apiurl = get_option("apiurl");
		$integratorKey = get_option("integratorKey");;
		$email = get_option("apiemail");
		$accountId = get_option("accountId");
		$password = get_option("apipassword");
		$apiQueries = 0; // Counter of DocuSign API requests

		// construct the authentication header:
		$header = "<DocuSignCredentials><Username>" . $email . "</Username><Password>" . $password . "</Password><IntegratorKey>" . $integratorKey . "</IntegratorKey></DocuSignCredentials>";
		$from_date = substr($Docs[0]->sendmaildate, 0, 10);
		$url = "$apiurl/accounts/$accountId/envelopes?from_date=$from_date"; // Get envelopes from the date of the oldest unsigned document

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("X-DocuSign-Authentication: $header"));
		$json_response = curl_exec($curl);
		$generalResponse = json_decode($json_response);
		curl_close($curl);
		$apiQueries++;

		if (isset($generalResponse->errorCode)) { //If DocuSign return error - show it.
			$_SESSION['ErrorMsg'][] = "Update Error: $response->message";
			return false;
		}
		
		foreach ($Docs as $Doc) { // Document data from DB
			$clientid = $Doc->id;
			$filenos = $Doc->file_number;
			$envelopeId = $Doc->envelopeId;
			$dbSignStatus = $Doc->docusignstatus;

			foreach ($generalResponse->envelopes as $envelope) {  // Document data from DocuSign
				if($envelopeId != $envelope->envelopeId) {
					continue;
				}

				$docusignstatus = $envelope->status;
				$envelopeIds = $envelope->envelopeId;

				if($docusignstatus == "sent" && $dbSignStatus != "sent") {
					//The "sent" status is set by default after sending the email after the simulation. This piece of code will be reproduced in the case that the DocuSign status from immediately after the stimulation and creation of the envelope was not equal to "sent".
					$url = "$apiurl/accounts/$accountId/envelopes/$envelopeIds";
					$curl = curl_init($url);
					curl_setopt($curl, CURLOPT_HEADER, false);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($curl, CURLOPT_HTTPHEADER, array("X-DocuSign-Authentication: $header"));
					$json_response = curl_exec($curl);
					$response = json_decode($json_response);
					curl_close($curl);
					$apiQueries++;

					if (isset($response->errorCode)) {
						$_SESSION['ErrorMsg'][] = "Record Updated Error: $filenos - $response->message";
						continue;
					}

					$sendmaildate = $response->sentDateTime;
					$SQL = "UPDATE wp_clientinfo SET sendmaildate='".esc_sql($sendmaildate)."',docusignstatus='".esc_sql($docusignstatus)."' WHERE id='".$clientid."' AND envelopeId='".esc_sql($envelopeId)."'";
					$wpdb->query($SQL);

				} else if($docusignstatus == "delivered" && $dbSignStatus != "delivered") {
					//The "delivered" status is the "Opened" status.
					$url = "$apiurl/accounts/$accountId/envelopes/$envelopeIds";
					$curl = curl_init($url);
					curl_setopt($curl, CURLOPT_HEADER, false);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($curl, CURLOPT_HTTPHEADER, array("X-DocuSign-Authentication: $header"));
					$json_response = curl_exec($curl);
					$response = json_decode($json_response);
					curl_close($curl);
					$apiQueries++;

					if (isset($response->errorCode)) {
						$_SESSION['ErrorMsg'][] = "Record Updated Error: $filenos - $response->message";
						continue;
					}

					$sendmaildate = $response->sentDateTime;	
					$opendate = $response->deliveredDateTime;
					$SQL = "UPDATE wp_clientinfo SET sendmaildate='".esc_sql($sendmaildate)."',opendate='".esc_sql($opendate)."',docusignstatus='".esc_sql($docusignstatus)."' WHERE id='".$clientid."' AND envelopeId='".esc_sql($envelopeId)."'";
					$wpdb->query($SQL);

				} else if($docusignstatus == "completed") {
					//The "completed" status is the "Signed" status.
					$url = "$apiurl/accounts/$accountId/envelopes/$envelopeIds";
					$curl = curl_init($url);
					curl_setopt($curl, CURLOPT_HEADER, false);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($curl, CURLOPT_HTTPHEADER, array("X-DocuSign-Authentication: $header"));
					$json_response = curl_exec($curl);
					$response = json_decode($json_response);
					curl_close($curl);
					$apiQueries++;

					if (isset($response->errorCode)) {
						$_SESSION['ErrorMsg'][] = "Record Updated Error: $filenos - $response->message";
						continue;
					}

					$sendmaildate = $response->sentDateTime;		
					$opendate = $response->deliveredDateTime;
					$signdate = $response->completedDateTime;

					$url = "$apiurl/accounts/$accountId/envelopes/$envelopeId/documents/";
					$curl = curl_init($url);
					curl_setopt($curl, CURLOPT_HEADER, false);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($curl, CURLOPT_HTTPHEADER, array("X-DocuSign-Authentication: $header"));
					$json_response = curl_exec($curl);
					$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
					$apiQueries++;

					if ( $status != 200 ) {
						echo "error calling webservice, status is:" . $status;
						exit(-1);
					}

					$response = json_decode($json_response, true); 
					curl_close($curl);

					if (!file_exists(ABSPATH.'wp-content/plugins/crm_new/files/'.$filenos)) {
						mkdir(ABSPATH.'wp-content/plugins/crm_new/files/'.$filenos, 0755, true);
					}

					$attachments = array();
					if(count($response) > 0) {
						foreach ($response['envelopeDocuments'] as $docs) {
							$filename = $docs['documentId']."-".$envelopeId."-".$docusignstatus;
							$url = "$apiurl/accounts/$accountId/envelopes/$envelopeId/documents/".$docs['documentId'];
							$curl = curl_init($url);
							curl_setopt($curl, CURLOPT_HEADER, false);
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($curl, CURLOPT_HTTPHEADER, array("X-DocuSign-Authentication: $header"));
							$json_response = curl_exec($curl);
							$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
							$apiQueries++;
								
							if($status != 200) {
								echo "error calling webservice, status is:" . $status;
								exit(-1);
							}

							curl_close($curl);
							file_put_contents(ABSPATH."wp-content/plugins/crm_new/files/".$filenos."/".$filename.".pdf",$json_response);
							$attachments[] = ABSPATH."wp-content/plugins/crm_new/files/".$filenos."/".$filename.".pdf";
						}
					}
					//======Send To Google Drive======//
					$docsarray = send_google_drive($clientid, $filenos, $attachments[0], $attachments[1]);
					$docusign1 = $docsarray[0];
					$docusign2 = $docsarray[1];
					//======Send To Google Drive======//
					$SQL = "UPDATE wp_clientinfo SET sendmaildate='".esc_sql($sendmaildate)."',opendate='".esc_sql($opendate)."',signdate='".esc_sql($signdate)."',docusignstatus='".esc_sql($docusignstatus)."',docusignorgs='',docusign1='".esc_sql($docusign1)."',docusign2='".esc_sql($docusign2)."' WHERE id='".$clientid."' AND envelopeId='".esc_sql($envelopeId)."'";
					$wpdb->query($SQL);
					$directory = ABSPATH.'wp-content/plugins/crm_new/files/'.$filenos;
					rmdir($directory);
				}
			}
		}
		$_SESSION['apiQueries'] = $apiQueries;
		return true;
	}
	 //======Send To Google Drive======//
		function send_google_drive($id,$fileno,$filename1,$filename2) {
			global $wpdb;
			require(ABSPATH.'/wp-content/themes/enemat/googledrives/vendor/autoload.php');
			if (!isset($client)) { //Prevent duplicate requests to the API GDrive
				$client = getClient();
			}
			$service = new Google_Service_Drive($client);
			$filenames = array();
			$filenames[] = $filename1;
			$filenames[] = $filename2;
			$parentfolders = get_option("parentfolders");
			$crmfolders = get_option("crmfolders");
			$parents = $_SESSION['GDrive']['parents'];
			$parentid = $_SESSION['GDrive']['parentid'];

			//===Root Folder's Sub Folder======//
			if(empty($parents)) { //Prevent duplicate requests to the API GDrive - parent folder remains the same during status update
				$results = $service->files->listFiles(
					['q' => "name = '$parentfolders' and mimeType = 'application/vnd.google-apps.folder'"] // Get only folder with name equal $parentfolders value
				);
			}
			
			try {
				if(empty($parents)) {//Prevent duplicate requests to the API GDrive - parent folder remains the same during status update
					foreach ($results->getFiles() as $item) {
						if ($item['name'] == $parentfolders) {
								$parents = $item['id'];
								break;
						}
					}
					$_SESSION['GDrive']['parents'] = $parents;
				}
				if(empty($parentid)) {//Prevent duplicate requests to the API GDrive - first subfolder remains the same during status update
					$optParams = array(
						'pageSize' => 10,
						'fields' => "nextPageToken, files(contentHints/thumbnail,fileExtension,iconLink,id,name,size,thumbnailLink,webContentLink,webViewLink,mimeType,parents)",
						'q' => "'".$parents."' in parents"
					);
					$results = $service->files->listFiles($optParams);
					$parentsarray = array();
					foreach ($results->getFiles() as $item) {
							$parentsarray[$item['name']] = $item->getId();		  
					}
					$parentid = $parentsarray[$crmfolders];
					$_SESSION['GDrive']['parentid'] = $parentid;
				}
			//===Root Folder's Sub Folder======//
			//===Sub Folder's Folders======//
				$optParams1 = array(
					'pageSize' => 10,
					'fields' => "nextPageToken, files(contentHints/thumbnail,fileExtension,iconLink,id,name,size,thumbnailLink,webContentLink,webViewLink,mimeType,parents)",
					'q' => "'".$parentid."' in parents"
				);
				$results = $service->files->listFiles($optParams1);
			//===Sub Folder's Folders======//	
			//==========CREATE FOLDER====//
				$childid = "";
				foreach ($results->getFiles() as $item) {
					if ($item['name'] == $fileno) {
						$childid = $item['id'];
							break;
						}
				}
				if(empty($childid)) {
					$fileMetadata = new Google_Service_Drive_DriveFile(array(
										'name' => $fileno,
										'parents'=>array($parentid),
										'mimeType' => 'application/vnd.google-apps.folder'));
										$file = $service->files->create($fileMetadata, array(
										'fields' => 'id'));
					$folderId = $file->id;
				} else {
					$folderId = $childid;
				}
				$dropboxurls = array();
				$counter = 0;
				foreach ($filenames as $filesends) {
					$counter = $counter + 1;
					if($counter == 1) {
						$fnames = "OFFRE DE PRIME - $fileno";
					} else if($counter == 2) {
						$fnames = "CERTIFICATE - $fileno";
					}
					if(!empty($filesends)) {
						$fileMetadata = new Google_Service_Drive_DriveFile(array(
							'name' => $fnames,
							'parents' => array($folderId)
						));
						$content = file_get_contents($filesends);
						$files = $service->files->create($fileMetadata, array(
							'data' => $content,
							'uploadType' => 'resumable',
							'fields' => 'id'
						));
						$fileids = $files->id;
						$dropboxurls[] = "https://drive.google.com/open?id=".$fileids."";
						$newPermission = new Google_Service_Drive_Permission();
						$newPermission->setType('anyone');
						$newPermission->setRole('reader');
						$service->permissions->create($fileids, $newPermission);
						@unlink(ABSPATH."wp-content/plugins/crm_new/files/".$fileno."/".basename($filesends));					
					}
				}
			} catch (Google_ServiceException $e) {
				$file_save = false;
				file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
			} catch (Google_IOException $e) {
				$file_save = false;
				file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
			} catch (Google_Exception $e) {
				$file_save = false;
				file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
			} catch (Exception $e) {
				$file_save = false;
				file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
			}
			return $dropboxurls;
		}

		function getClient() {
			$client = new Google_Client();
			$client->setApplicationName('Google Drive API PHP Quickstart');
			$client->setScopes(Google_Service_Drive::DRIVE);
			$client->setAuthConfig(ABSPATH.'/wp-content/themes/enemat/googledrives/credentials.json');
			return $client;
		}
		//======Send To Google Drive======//
	if(isset($_GET['update_docusign']) && $_GET['update_docusign'] == "yes") {
		$SQLDOCS = "SELECT * FROM  wp_clientinfo ORDER BY sendmaildate ASC"; // Sort by date of sending the mail, the oldest - the first.
		$rsDocs = $wpdb->get_results($SQLDOCS);

		if(count($rsDocs)) {
			// From the resulting array, delete the records with the status "completed", then send the entire array to the DocuSign status update function.
			foreach ($rsDocs as $key => $Docs) {
				if(empty($Docs->envelopeId) || $Docs->docusignstatus == "completed") {
					unset($rsDocs[$key]);
				}
			}

			if( update_docusignapis(array_values($rsDocs)) === true) {
				$_SESSION['SuccessMsg'] = "Record Updated Successfully.....";
			}

			unset($_SESSION['GDrive']); // Clear parent and subfolder id's from this session.
			wp_redirect($siteurl."/wp-admin/admin.php?page=crm_new");	
			exit; 
		}
	}
	//==Update Docusign API==//
	$datemontharray = array("01"=>"Janvier","02"=>"Février","03"=>"Mars","04"=>"Avril","05"=>"Mai","06"=>"Juin","07"=>"Juillet","08"=>"Août","09"=>"Septembre","10"=>"Octobre","11"=>"Novembre","12"=>"Décembre");
    $search_by_array= array("file_number"=>"(30)-NUMERO DOSSIER","pusalesname"=>"(0)-COMMERCIAL","pucompany_name"=>"(10)INSTALLATEUR","pusociety_number"=>"(11)SIRET","pufirst_name"=>"(2) PU NOM","pusociety_postcode"=>"(13) CP","pucity"=>"(14) PU Ville","name"=>"(16) CLIENT NOM","ville"=>"(22) CLIENT Ville","client_society_name"=>"(36) Nom societé","de_siren"=>"(37) SIREN","fiche"=>"(23) FICHE");
	$SQLEXPORTSEXCEL = "SELECT * FROM wp_exportcsv ORDER BY id DESC LIMIT 1";
	$rsExcels = $wpdb->get_results($SQLEXPORTSEXCEL);
	?>
<style>
input[type="file"] {
    display: none;
}
</style>	
<link href="https://fonts.googleapis.com/css?family=Roboto:100,100i,400,500,500i,700,900" rel="stylesheet">
 <link href="<?php echo plugins_url('crmnew/css/style.css' , dirname(__FILE__));?>" rel="stylesheet" type="text/css" />
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="<?php echo plugins_url('crmnew/js/validate.js' , dirname(__FILE__));?>"></script> 
<div class="template_sectdiv">
<div id="myModal" class="modal nresectablredit" style="display:none;"></div>
<div class="templ_headerpart">
  <div class="search_sec1">
 <form name="frmsearch" id="frmsearch" action="" method="Post">
 <input type="text" name="keywords" id="keywords" value="<?php echo $keywords;?>" placeholder="Search information">
 <input type="submit" name="btnsearch" id="btnsearch" value="">
 <?php if($roles=="administrator" || $roles=="superadmin"){?>
				<input type="button" name="btnapis" id="btnapis" value="Update DocuSign" onclick="return update_docusign('<?php echo  $siteurl;?>/wp-admin/admin.php?page=crm_new&update_docusign=yes');"/>
				<?php if(count($rsExcels)>0){?>
				<a href="<?php echo $rsExcels[0]->driveboxurls;?>" target="_blank"><input type="button" name="btnexports" id="btnexports" value="Export Excel" style="cursor:pointer;"></a>
				 <?php }?>
				 <input type="button" name="btndelall" id="btndelall" onclick="return multipledelete();" value="Delete All">
				<?php }?>
  </form>
 
  </div>
	<?php
		if(isset($_SESSION['SuccessMsg'])){?>
			<div class="success-msg"><?php echo $_SESSION['SuccessMsg'];unset( $_SESSION['SuccessMsg']);?></div>
		<?php }?>
</div>	
<div class="frsttblesectfr">
	<?php if(isset($_SESSION['apiQueries'])) { // Show API requests counter ?>
		<!-- <div class="counter-msg"><?php //If you need to display the number of DocuSign API requests, please uncomment these three lines.
			// echo "Number of DocuSign API requests: " . $_SESSION['apiQueries'];
			unset($_SESSION['apiQueries']); ?>
		</div> -->
	<?php } ?>
	<?php if(isset($_SESSION['ErrorMsg'])) { // Show error message ?>
		<pre class="error-msg"><?php
			foreach ($_SESSION['ErrorMsg'] as $ErrorMsg) {
				echo $ErrorMsg . "\n";
			}
			unset($_SESSION['ErrorMsg']); ?>
		</pre>
	<?php } ?>
	<form name="frmevents" id="frmevents" action="" method="Post" enctype="multipart/form-data">
		<?php if($roles == "administrator") { 				
			include("administrator_view.php");
		} else if($roles == "superadmin" || $roles == "admin") {
			// include("puview.php");
			include("superadmin_view.php");
		} else if($roles == "professionaluser") {
			include("puview.php");
		} ?>
	</form>
</div>
</div>