<?php 
// Template name: Cronjobs

global $wpdb;

function update_docusignapis($clientid,$filenos,$envelopeId){
		global $wpdb;
		$apiurl = get_option("apiurl");
		$integratorKey = get_option("integratorKey");;
		$email = get_option("apiemail");
		$accountId = get_option("accountId");
		$password = get_option("apipassword");
		date_default_timezone_set("Europe/Paris"); 
		// construct the authentication header:
		$header = "<DocuSignCredentials><Username>" . $email . "</Username><Password>" . $password . "</Password><IntegratorKey>" . $integratorKey . "</IntegratorKey></DocuSignCredentials>";
		$url = $apiurl."/accounts/".$accountId."/envelopes/".$envelopeId."";
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("X-DocuSign-Authentication: $header"));
		$json_response = curl_exec($curl);
		$response = json_decode($json_response);
		$docusignstatus = $response->status;
		$envelopeIds = $response->envelopeId;
		$envelopeIds = $response->envelopeId;
		if($docusignstatus=="sent"){
			$sendmaildate = $response->sentDateTime;
			$SQL = "UPDATE wp_clientinfo SET sendmaildate='".esc_sql($sendmaildate)."',docusignstatus='".esc_sql($docusignstatus)."' WHERE id='".$clientid."' AND envelopeId='".esc_sql($envelopeId)."'";
			$wpdb->query($SQL);
		}else if($docusignstatus=="delivered"){
			$sendmaildate = $response->sentDateTime;			
			$opendate = $response->deliveredDateTime;
			$SQL = "UPDATE wp_clientinfo SET sendmaildate='".esc_sql($sendmaildate)."',opendate='".esc_sql($opendate)."',docusignstatus='".esc_sql($docusignstatus)."' WHERE id='".$clientid."' AND envelopeId='".esc_sql($envelopeId)."'";
			$wpdb->query($SQL);
		}else if($docusignstatus=="completed"){
			$sendmaildate = $response->sentDateTime;			
			$opendate = $response->deliveredDateTime;
			$signdate = $response->completedDateTime;
			$header = "<DocuSignCredentials><Username>" . $email . "</Username><Password>" . $password . "</Password><IntegratorKey>" . $integratorKey . "</IntegratorKey></DocuSignCredentials>";
				$url = $apiurl."/accounts/".$accountId."/envelopes/".$envelopeId."/documents/";
				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_HTTPHEADER, array("X-DocuSign-Authentication: $header"));
				$json_response = curl_exec($curl);
				$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				if ( $status != 200 ) {
					echo "error calling webservice, status is:" . $status;
					exit(-1);
				}
				$response = json_decode($json_response, true); 
				curl_close($curl);
				//  echo "<pre>";
				// print_r($response);
				if (!file_exists(ABSPATH.'wp-content/plugins/crm/files/'.$filenos)) {
					mkdir(ABSPATH.'wp-content/plugins/crm/files/'.$filenos, 0755, true);
				}
				$attachments = array();
				if(count($response)>0){
					foreach($response['envelopeDocuments'] as $docs){
						$filename = $docs['documentId']."-".$envelopeId."-".$docusignstatus;
						$url = $apiurl."/accounts/".$accountId."/envelopes/".$envelopeId."/documents/".$docs['documentId']."";
						$curl = curl_init($url);
						curl_setopt($curl, CURLOPT_HEADER, false);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($curl, CURLOPT_HTTPHEADER, array("X-DocuSign-Authentication: $header"));
						$json_response = curl_exec($curl);
						$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
						//echo $json_response;
						
						if ( $status != 200 ) {
							echo "error calling webservice, status is:" . $status;
							exit(-1);
						}
						curl_close($curl);
						file_put_contents(ABSPATH."wp-content/plugins/crm/files/".$filenos."/".$filename.".pdf",$json_response);
						$attachments[] = ABSPATH."wp-content/plugins/crm/files/".$filenos."/".$filename.".pdf";
					}
				}
			
			//======Send To Google Drive======//
			$docsarray = send_google_drives($clientid,$filenos,$attachments[0],$attachments[1]);
			$docusign1 = $docsarray[0];
			$docusign2 = $docsarray[1];
			//======Send To Google Drive======//
			
			$SQL = "UPDATE wp_clientinfo SET sendmaildate='".esc_sql($sendmaildate)."',opendate='".esc_sql($opendate)."',signdate='".esc_sql($signdate)."',docusignstatus='".esc_sql($docusignstatus)."',docusignorgs='',docusign1='".esc_sql($docusign1)."',docusign2='".esc_sql($docusign2)."' WHERE id='".$clientid."' AND envelopeId='".esc_sql($envelopeId)."'";
			$wpdb->query($SQL);
		}
		
		 return true;
}
			 //======Send To Google Drive======//
		function send_google_drives($id,$fileno,$filename1,$filename2){
			global $wpdb;
			require(ABSPATH.'/wp-content/themes/enemat/googledrives/vendor/autoload.php');
			$client = getClients();
			$service = new Google_Service_Drive($client);
			$filenames = array();
			$filenames[] = $filename1;
			$filenames[] = $filename2;
			$parentfolders = get_option("parentfolders");
			$crmfolders = get_option("crmfolders");
			//===Root Folder's Sub Folder======//			
			$results = $service->files->listFiles();
			$parents = "";
			try{
			foreach ($results->getFiles() as $item) {
				if ($item['name'] == $parentfolders) {
						$parents = $item['id'];
						break;
				}
			}
			
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
			if(empty($childid)){
				$fileMetadata = new Google_Service_Drive_DriveFile(array(
									'name' => $fileno,
									'parents'=>array($parentid),
									'mimeType' => 'application/vnd.google-apps.folder'));
									$file = $service->files->create($fileMetadata, array(
									'fields' => 'id'));
				 $folderId = $file->id;
			 }else{
				$folderId = $childid;
			 }
			 $googledriveurls = array();
			 $counter = 0;
			 foreach($filenames as $filesends){
				$counter = $counter+1;
				if($counter==1){
					$fnames = "OFFRE DE PRIME - ".$fileno."";
				}else if($counter==2){
					$fnames = "CERTIFICATE - ".$fileno."";
				}
				 if(!empty($filesends)){
					$fileMetadata = new Google_Service_Drive_DriveFile(array(
								'name' => $fnames,
								'parents' => array($folderId)
							));
							$content = file_get_contents($filesends);
							$files = $service->files->create($fileMetadata, array(
									'data' => $content,
									'uploadType' => 'resumable',
									'fields' => 'id'));
					$fileids = $files->id;
					$googledriveurls[] = 	"https://drive.google.com/open?id=".$fileids."";
					$newPermission = new Google_Service_Drive_Permission();
					$newPermission->setType('anyone');
					$newPermission->setRole('reader');
					$service->permissions->create($fileids, $newPermission);
					@unlink(ABSPATH."wp-content/plugins/crm/files/".$fileno."/".basename($filesends));					
				}
			}
			}catch (Google_ServiceException $e) {
				   $file_save = false;
				   file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
				}catch (Google_IOException $e) {
				  $file_save = false;
				  file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
				} catch (Google_Exception $e) {
				  $file_save = false;
				  file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
				}catch (Exception $e) {
					$file_save = false;
					file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
				}
			return $googledriveurls;
			 
		}
		function getClients(){
			$client = new Google_Client();
			$client->setApplicationName('Google Drive API PHP Quickstart');
			$client->setScopes(Google_Service_Drive::DRIVE);
			$client->setAuthConfig(ABSPATH.'/wp-content/themes/enemat/googledrives/credentials.json');
			return $client;
		}
		//======Send To Google Drive======//
if(isset($_GET['update_docusign']) && $_GET['update_docusign']=="yes"){
		$SQLDOCS = "SELECT * FROM  wp_clientinfo ORDER BY id DESC";
		$rsDocs = $wpdb->get_results($SQLDOCS);
		if(count($rsDocs)){
				 for($j=0;$j<count($rsDocs);$j++){	
					$ID = $rsDocs[$j]->id;
					if(!empty($rsDocs[$j]->envelopeId) && $rsDocs[$j]->docusignstatus!="completed"){
						 update_docusignapis($rsDocs[$j]->id,$rsDocs[$j]->file_number,$rsDocs[$j]->envelopeId);					 				 
					}
			}			 
		}
}