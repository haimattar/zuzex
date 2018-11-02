<?php
	  include('../../../wp-load.php');
		 $id = $_POST['id'];
		 $filenos = $_POST['filenos'];
		 $siteurl = get_option("siteurl");
		 $current_user_id = get_current_user_id();
		 $user = new WP_User( $current_user_id );
		 foreach( $user->roles as $role ) {
					$role = get_role( $role );
			if ( $role != null )
			 $userroles = $role->name;
		  }
		 global $wpdb;	
		 // print_r($_FILES);exit;
		 if ( 0 < $_FILES['file']['error'] ) {
			$errors = 'Error: ' . $_FILES['file']['error'] . '<br>';
			$array_response = array("opendate"=>"","signdate"=>"");				 
			echo json_encode($array_response);
		}else{
			if (!file_exists(ABSPATH.'wp-content/plugins/crm/files/'.$filenos)) {
				 mkdir(ABSPATH.'wp-content/plugins/crm/files/'.$filenos, 0755, true);
			}
			
			$file_name = $_FILES['file']['name'];
			$target_path = ABSPATH.'wp-content/plugins/crm/files/'.$filenos.'/' .$_FILES['file']['name'];
			move_uploaded_file($_FILES['file']['tmp_name'], $target_path);
			 $documents = $siteurl."/wp-content/plugins/crm/files/".$filenos."/".$file_name;
			 
			 //======SEND FILES TO GOOGLE DRIVE========//
			 $googledriveurl = send_google_drive($id,$filenos,$target_path);
			 //======SEND FILES TO GOOGLE DRIVE========//
			 //===Remove Image From Folder===//				 
				@unlink(ABSPATH.'wp-content/plugins/crm/files/'.$filenos.'/'.basename($target_path));
				
				$directory = ABSPATH.'wp-content/plugins/crm/files/'.$filenos;
				rmdir($directory);
			 //===Remove Image From Folder===//
			 //======Get Dicusignindocs======//
			   $SQLDOCS = "SELECT envelopeId,usertype FROM  wp_clientinfo WHERE id='".$id."'";
			   $rsDocs = $wpdb->get_results($SQLDOCS);
			   $updatedby = $rsDocs[0]->usertype;
			  
			  $SQLUPDATE = "UPDATE wp_clientinfo SET opendate=now(),signdate=now(),docusignorgs='',docusign1='".esc_sql($googledriveurl)."',docusignupdatedby='".$userroles."' WHERE id='".$id."'";
			  $wpdb->query($SQLUPDATE);
			 //======Get Dicusignindocs======//			
			 //==Send Mail==//
			$SQL = "SELECT opendate,signdate,docusign1 FROM  wp_clientinfo WHERE id='".$id."'";
			$rs = $wpdb->get_results($SQL,ARRAY_A);
			$opendate = date("d/m/Y", strtotime($rs[0]['opendate'])).'<br/><span class="brtimeclass">'.date("h:i A", strtotime($rs[0]['opendate'])).'</span>'; 
			$signdate =date("d/m/Y", strtotime($rs[0]['signdate'])).'<br/><span class="brtimeclass">'.date("h:i A", strtotime($rs[0]['signdate'])).'</span>'; 
			$certificates = '<a href="'. $rs[0]['docusign1'].'" target="_blank"><h4>CERTIFICATE</h4></a>'; 			 
			$array_response = array("opendate"=>$opendate,"signdate"=>$signdate,"certificates"=>$certificates);				 
			echo json_encode($array_response);
		}
		
		
		//======Send To Google Drive======//
		function send_google_drive($id,$fileno,$filename){
			global $wpdb;
			require(ABSPATH.'/wp-content/themes/enemat/googledrives/vendor/autoload.php');
			$client = getClient();
			$service = new Google_Service_Drive($client);
			$results = $service->files->listFiles();
			$parentfolders = get_option("parentfolders");
				$crmfolders = get_option("crmfolders");
			try{
				$service = new Google_Service_Drive($client);
				//===Root Folder's Sub Folder======//
				$results = $service->files->listFiles(
					['q' => "name = '$parentfolders' and mimeType = 'application/vnd.google-apps.folder'"] // Get only folder with name equal $parentfolders value
				);
				$parents = "";
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
			 $newPermission = new Google_Service_Drive_Permission();
			 $newPermission->setType('anyone');
			 $newPermission->setRole('reader');
			 $service->permissions->create($folderId, $newPermission);
			  if(!empty($filename)){
				try{
					$fileMetadata = new Google_Service_Drive_DriveFile(array(
								'name' => "OFFRE DE PRIME - ".$fileno."",
								'parents' => array($folderId)
							));
					$content = file_get_contents($filename);
					$files = $service->files->create($fileMetadata, array(
									'data' => $content,
									'uploadType' => 'resumable',
									'fields' => 'id'));	
					$fileids = $files->id; 
					$newPermission = new Google_Service_Drive_Permission();
					$newPermission->setType('anyone');
					$newPermission->setRole('reader');
					$service->permissions->create($fileids, $newPermission);
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
			}
			$driveurls = "https://drive.google.com/open?id=".$fileids."";
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
			return $driveurls;		 
			 
		}
		function getClient(){
			$client = new Google_Client();
			$client->setApplicationName('Google Drive API PHP Quickstart');
			$client->setScopes(Google_Service_Drive::DRIVE);
			$client->setAuthConfig(ABSPATH.'/wp-content/themes/enemat/googledrives/credentials.json');
			return $client;
		}
		//======Send To Google Drive======//
		 ?>