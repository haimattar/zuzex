<?php
// Template name: Cronjobs

global $wpdb;

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

		update_docusignapis(array_values($rsDocs));

		unset($_SESSION['cronGDrive']); // Clear parent and subfolder id's from this session.
		unset($_SESSION['cronErrorMsg']);
		unset($_SESSION['apiQueriesCron']);
	}
}

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
		$_SESSION['cronErrorMsg'][] = "Update Error: $response->message";
		return false;
	}
	
	foreach ($Docs as $Doc) { // Document data from DB
		if($apiQueries > 500) { //Stop the session if the number of requests to the DocuSign API exceeds 500
			break;
		}
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
					$_SESSION['cronErrorMsg'][] = "Record Updated Error: $filenos - $response->message";
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
					$_SESSION['cronErrorMsg'][] = "Record Updated Error: $filenos - $response->message";
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
					$_SESSION['cronErrorMsg'][] = "Record Updated Error: $filenos - $response->message";
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
	$_SESSION['apiQueriesCron'] = $apiQueries;
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
	$parents = $_SESSION['cronGDrive']['parents'];
	$parentid = $_SESSION['cronGDrive']['parentid'];

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
			$_SESSION['cronGDrive']['parents'] = $parents;
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
			$_SESSION['cronGDrive']['parentid'] = $parentid;
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
				'mimeType' => 'application/vnd.google-apps.folder'
			));
			$file = $service->files->create($fileMetadata, array(
				'fields' => 'id'
			));
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