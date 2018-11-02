<link href="<?php echo plugins_url('crmnew/css/style.css' , dirname(__FILE__));?>" rel="stylesheet" type="text/css" />
	<table width="100%" cellpadding="0" cellspacing="2" class="firstidmsscl sav_tbs">
		<thead>
		<tr class="newsecteddaulp">
		<?php if($roles=="superadmin"){?> 
		<td class="checkus5sw"><input type="checkbox" name="checkall" id="checkall" value="checkall" /></td>
		
		<?php }?>
		   <th colspan="3" class="daupsec1">DATE CHANTIER</th>
		   <th  colspan="4" class="daupsec2">PROFESSIONAL USER / ADMIN INFORMATION</th>
		   <th  colspan="4" class="daupsec3">CLIENT INFO</th>
		   <th colspan="4"  class="daupsec4">SCENARIO DATA</th>
		   <th colspan="4"  class="daupsec5">DOCUMENT FOR THE SCENARIO</th>
		   <th colspan="2" class="daupsec6 dauclrsred">PRIMES</th>
		   <th class="daupsec7 dauclrsrednew"><div id="sum4">
								<strong><?php 
								if(strstr($rsTotals[0]->TOTALPROFITS,"-")){
									echo "-€".str_replace("-","",number_format($rsTotals[0]->TOTALPROFITS,0," "," "));
								}else{
									echo "€".number_format($rsTotals[0]->TOTALPROFITS,0," "," ");
								}
								?></strong></div></th>
		</tr>


		<tr class="newsecteddaulp1 newsect_p1newtextdes">
				<?php if($roles=="superadmin"){?> 
		<td class="checkus5sw">&nbsp;</td>
		
		<?php }?>
				<td class="daupsec08 td-visible"><h6>Numero Dossier</h6><span>30</span></td>
				<td class="daupsec09"><h6>Statut </h6><span>&nbsp;</span></td>
				<td class="daupsec010"><h6>Begin Debut</h6> <span>40</span></td>
				<td class="daupsec011"><h6>PU / ADMIN</h6> <span>39</span></td>
				<td class="daupsec012"><h6>Commercial </h6> <span>0</span></td>
				<td class="daupsec013"> <h6>Installateur</h6> <span>10</span></td>
				<td class="daupsec014"><h6>RGE</h6> <span>41</span></td>


				<td class="daupsec015"><h6>Nom</h6><span>16</span></td>
				<td class="daupsec016"><h6>Prenom</h6><span>15</span></td>
				<td class="daupsec017"><h6>PRÉCARITÉ</h6><span>48</span></td>
				<td class="daupsec018"><h6>FULL VIEW</h6> <span>&nbsp;</span></td>


				<td class="daupsec019"><h6>Fiche</h6><span>23</span></td>
				<td class="daupsec020"><h6>Prime Client €</h6><span>31</span></td>
				<td class="daupsec021"><h6>Classique mWh</h6><span>32</span></td>
				<td class="daupsec022"><h6>Precarite mWh</h6><span>34</span></td>



				<td class="daupsec023">
				
				<table width="100%" cellpadding="0" cellspacing="2" class="firstidmsscl10ac">
				 <tr>
				<td><h6>ENVOYÉ</h6><span>D1</span></td>
				<td class="daupsec024"><h6>CONSULTÉ</h6><span>42</span></td>
				<td class="daupsec025"><h6>SIGNÉ</h6><span>D2</span></td>
				 <td class="daupsec025b">&nbsp;</td>
				 </tr>
				
				
				</table>
				
				<td class="daupsec026"><h6>DEVIS</h6><span>43</span></td>
				<td class="daupsec027"><h6>FACTURE</h6><span>44</span></td>
				<td class="daupsec028 mys28"><h6>AH</h6><span>45</span></td>


				<td class="daupsec028 dauclrsred"><h6>Classique</h6><span>7</span></td>
				<td class="daupsec029 dauclrsred"><h6>Precarite</h6><span>8</span></td>
				<td class="daupsec030 dauclrsrednew"><h6>PROFIT</h6><span>39</span></td>


		</tr>
		</thead>
		<tbody class="rup_fx11">
		<?php  if(count($rs)){
							 for($i=0;$i<count($rs);$i++){	
								$ID = $rs[$i]->id;
								$pmetaid = $rs[$i]->pmetaid;
								if(!empty($rs[$i]->envelopeId) && $rs[$i]->docusignstatus!="completed"){
									 //update_docusignapis($rs[$i]->id,$rs[$i]->envelopeId);
									//echo "<pre>";
									//print_r($aa);						 
								}
								if($rs[$i]->status=="Classic"){
									$tdclasscolors = "tbledvfdcoloc";
								}else if($rs[$i]->status=="Prec"){
									$tdclasscolors = "tbledvfdcoloc1";
								
								}else if($rs[$i]->status=="Grandprec"){								
									$tdclasscolors = "tbledvfdcoloc2";
								}
								$tdlasstdcolors = "";
								if(!empty($rs[$i]->opendate) && empty($rs[$i]->signdate)){
									$tdlasstdcolors = "tbledvfdcoloc21";
								}else if(!empty($rs[$i]->signdate)){
									$tdlasstdcolors = "tbledvfdcoloc22";
								}
								if(strstr($rs[$i]->fiche,"BAR")){
									$tdclassbar = "tdclassbars";
								}else{
									$tdclassbar = "tdclassnotbars";
								}
								$uploadocusignorgs = "";
								if(empty($rs[$i]->docusignorgs)){
									$uploadocusignorgs = "uploadocusignorgs";
								}
								/*$SQLPOSTMETAUPDATES = "UPDATE wp_project_meta SET client_status='Yes' WHERE id='".$pmetaid."'";
								 $wpdb->query($SQLPOSTMETAUPDATES);*/
							 ?>
		 <tr class="newsecteddaulp1">
				<?php if($roles=="superadmin"){?><td class="checkus5sw"><input type="checkbox" name="delid[]" id="delid<?php echo $i;?>" value="<?php echo $ID;?>" class="checkBoxClass"/></td>
				<?php }?>
				
			
				<td class="daupsec08 daupsecup023dd td-visible"><?php if(!empty($rs[$i]->folderid)){?>			 
								 <a href="<?php echo $rs[$i]->folderid;?>" target="_blank"><?php echo $rs[$i]->file_number;?></a>
								 <?php }else{
									echo $rs[$i]->file_number; 
								 }?></td>
				<td class="daupsec09">&nbsp;</td>
				<td class="daupsec010"><?php 
								if(!empty($rs[$i]->debuit_begin)){
									$debuit_begin = explode("-",$rs[$i]->debuit_begin);
									echo $datemontharray[$debuit_begin[1]].", ".$debuit_begin[0];
								}	
								?></td>
				<td class="daupsec011"><?php 
											if(!empty($rs[$i]->usertype)){
											if($rs[$i]->usertype=="professionaluser"){
												echo "PU";
											}else if($rs[$i]->usertype=="admin"){
												echo "Admin";
											}else{
												echo "Superadmin";
											}
											}
											?></td>
				<td class="daupsec012"><?php echo $rs[$i]->pusalesname;?></td>
				<td class="daupsec013"><?php echo $rs[$i]->pucompany_name;?></td>
				<td class="daupsec014">
						<div id="divsdatedoc11<?php echo $i;?>" style="display:none;" class="divsecclsssection0">
						<?php if(!empty($rs[$i]->document11senddate)){
							echo date("d/m/Y",strtotime($rs[$i]->document11senddate));
							}?>						
							</div>
							<div id="divsdoc11<?php echo $i;?>" class="divsecclsssection01">
								<div id="divsdocdoc11<?php echo $i;?>" class="divsecclsssection1">
								<?php if(!empty($rs[$i]->document11)){?>
									<a href="<?php echo $rs[$i]->document11;?>" target="_blank"><h4><img class="greentickmark" src="<?php echo plugins_url('crmnew/images/img4.png' , dirname(__FILE__));?>"></h4></a>
								<?php }else{?>
									<label> <img src="<?php echo plugins_url('crmnew/images/img5.png' , dirname(__FILE__));?>">
								<input type="file" name="doc11<?php echo $i;?>" id="doc11<?php echo $i;?>" onchange="return upload_attachment('<?php echo $rs[$i]->id;?>','<?php echo $i;?>','doc11','<?php echo $rs[$i]->file_number;?>','document11','<?php echo plugins_url('crmnew/send_attachments.php' ,dirname(__FILE__));?>');"/></label> 

								<?php }?>
								</div>
								<?php if(!empty($rs[$i]->document11)){?>
								<label> <img class="rgtsomimg" src="<?php echo plugins_url('crmnew/images/img5.png' , dirname(__FILE__));?>">
								<input type="file" name="doc11<?php echo $i;?>" id="doc11<?php echo $i;?>" onchange="return upload_attachment('<?php echo $rs[$i]->id;?>','<?php echo $i;?>','doc11','<?php echo $rs[$i]->file_number;?>','document11','<?php echo plugins_url('crmnew/send_attachments.php' ,dirname(__FILE__));?>');"/></label> 
								<?php }?>
								<div id="divssubdoc11<?php echo $i;?>" style="display:none;">
									<label> <img class="rgtsomimg" src="<?php echo plugins_url('crmnew/images/img5.png' , dirname(__FILE__));?>">
								<input type="file" name="doc11<?php echo $i;?>" id="doc11<?php echo $i;?>" onchange="return upload_attachment('<?php echo $rs[$i]->id;?>','<?php echo $i;?>','doc11','<?php echo $rs[$i]->file_number;?>','document11','<?php echo plugins_url('crmnew/send_attachments.php' ,dirname(__FILE__));?>');"/></label> 
								</div>
								</div>
								<div id="divssendingdoc11<?php echo $i;?>" style="display:none;" class="divsecclsssection2"></div>	</td>
				<td class="daupsec015"><?php echo $rs[$i]->name;?></td>
				<td class="daupsec016"><?php echo $rs[$i]->fname;?></td>
				<td class="daupsec017 <?php echo $tdclasscolors." ".$tdclassbar;?>" id="tdclassfieds<?php echo $i;?>">
					<?php if(strstr($rs[$i]->fiche,"BAR")){?>
								<div id="divsdocclassifieds<?php echo $i;?>" class="divsecclsssection01">
								<div id="divsdocdocclassifieds<?php echo $i;?>" class="divsecclsssection1 my_drop">

								<?php if(!empty($rs[$i]->documentclassifieds)){?>
									<a href="<?php echo $rs[$i]->documentclassifieds;?>" target="_blank"><h4><img id="img-<?php echo $i;?>" style="display:none" class="greentickmark2" src="<?php echo plugins_url('crmnew/images/img4.png' , dirname(__FILE__));?>"></h4></a>
								<?php }else{?>
								
									<label> <img class="dw_img" src="<?php echo plugins_url('crmnew/images/img5.png' , dirname(__FILE__));?>">
								<input type="file" name="docclassifieds<?php echo $i;?>" id="docclassifieds<?php echo $i;?>" onchange="return upload_attachment('<?php echo $rs[$i]->id;?>','<?php echo $i;?>','docclassifieds','<?php echo $rs[$i]->file_number;?>','documentclassifieds','<?php echo plugins_url('crmnew/send_attachments.php' ,dirname(__FILE__));?>');"/></label> 

								<?php }?>
								</div>
								<?php if(!empty($rs[$i]->documentclassifieds)){?>
								<label> <img class="rgtsomimg" src="<?php echo plugins_url('crmnew/images/img5.png' , dirname(__FILE__));?>">
								<input type="file" name="docclassifieds<?php echo $i;?>" id="docclassifieds<?php echo $i;?>" onchange="return upload_attachment('<?php echo $rs[$i]->id;?>','<?php echo $i;?>','docclassifieds','<?php echo $rs[$i]->file_number;?>','documentclassifieds','<?php echo plugins_url('crmnew/send_attachments.php' ,dirname(__FILE__));?>');"/></label> 
								<?php }?>
								<div id="divssubdocclassifieds<?php echo $i;?>" style="display:none;">
									<label> <img class="rgtsomimg" src="<?php echo plugins_url('crmnew/images/img5.png' , dirname(__FILE__));?>">
								<input type="file" name="docclassifieds<?php echo $i;?>" id="docclassifieds<?php echo $i;?>" onchange="return upload_attachment('<?php echo $rs[$i]->id;?>','<?php echo $i;?>','docclassifieds','<?php echo $rs[$i]->file_number;?>','documentclassifieds','<?php echo plugins_url('crmnew/send_attachments.php' ,dirname(__FILE__));?>');"/></label> 
								</div>
								</div>
								<?php }else{?>
									<div id="divsdocclassifieds<?php echo $i;?>" class="divsecclsssection01">
								<div id="divsdocdocclassifieds<?php echo $i;?>" class="divsecclsssection1">
								<?php if(!empty($rs[$i]->documentclassifieds)){?>
									<a href="<?php echo $rs[$i]->documentclassifieds;?>" target="_blank"><h4><img class="greentickmark" src="<?php echo plugins_url('crmnew/images/img4.png' , dirname(__FILE__));?>"></h4></a>
								<?php }?>
								</div>								  
								</div>
								<?php }?>
					<?php if(strstr($rs[$i]->fiche,"BAR")){?>
								<select name="status" id="status<?php echo $i;?>" class="<?php echo $tdclasscolors;?>" onchange="return change_status(this.value,'<?php echo $ID;?>','<?php echo $i;?>','<?php echo plugins_url('crmnew/update_status.php' ,dirname(__FILE__));?>');">
									<option value="Classic" <?php if($rs[$i]->status=="Classic"){?>selected="selected"<?php }?>>Classique</option>
									<option value="Prec" <?php if($rs[$i]->status=="Prec"){?>selected="selected"<?php }?>>Precaire</option>
									<option value="Grandprec" <?php if($rs[$i]->status=="Grandprec"){?>selected="selected"<?php }?>>Grand Precaire</option>						
								</select>
								<?php }else{ 
									echo "Classique";
								}?>
								<div id="divsdatedocclassifieds<?php echo $i;?>" class="divsecclsssection3" style="display:none;"><?php if(!empty($rs[$i]->docclassifiedssenddate)){
							echo date('d/m/Y',strtotime($rs[$i]->docclassifiedssenddate));}?>
							</div>									
								</td>
				<td class="daupsec018"><span  onclick="return open_box('<?php echo $i;?>','<?php echo $rs[$i]->id;?>','<?php echo plugins_url('crmnew/view_details.php' ,dirname(__FILE__));?>');">View</span>
		  
								
		  </td>


				<td class="daupsec019"><a href="<?php echo $rs[$i]->fichelink;?>" target="_blank"><?php echo $rs[$i]->fiche;?></a></td>
				<td class="daupsec020 daupsecup023dd"><div id="crpr<?php echo $ID;?>"><?php echo number_format($rs[$i]->class_prime,0," "," ")." €";?></div></td>
				<td class="daupsec021"><div id="crpb<?php echo $ID;?>"><?php 
								if($rs[$i]->class_bonus!=0){
									echo number_format($rs[$i]->class_bonus,3, ',', ' ');
								}else{
									echo "";
								}
								?></div></td>
				<td class="daupsec022 <?php echo $tdclasscolors;?>"><div id="prpb<?php echo $ID;?>"><?php 
								if($rs[$i]->prec_bonus!=0){
									echo number_format($rs[$i]->prec_bonus,3, ',', ' ');
								}else{
									echo "";
								}
								?></div></td>
<!---3col add---->
<td class="daupsec023 <?php echo $tdlasstdcolors;?>">
	<table width="100%" cellpadding="0" cellspacing="2" class="firstidmsscl10ac firstidmsscl10ac2 <?php echo $tdlasstdcolors;?>">
<tbody id="tbody<?php echo $i;?>" class="<?php echo $tdlasstdcolors;?>">
	<tr>
		<td class="daupsec0023 <?php echo $tdlasstdcolors;?>"><span class="datesecrder"><?php echo date("d/m/Y", strtotime($rs[$i]->sendmaildate));
					echo '<br/>';
					echo '<span class="brtimeclass">'.date("h:i A", strtotime($rs[$i]->sendmaildate)).'</span>';
					?>
				
					</div>			
				</td>
				<td class="daupsec024 <?php echo $tdlasstdcolors;?>"><div id="divopendate<?php echo $i;?>">
				<?php if(!empty($rs[$i]->opendate)){echo date("d/m/Y", strtotime($rs[$i]->opendate));
					echo '<br/>';
					echo '<span class="brtimeclass">'.date("h:i A", strtotime($rs[$i]->opendate)).'</span>';?></h4>		
				<?php }?>
				</div></td>
				<td class="daupsec025 <?php echo $tdlasstdcolors;?>"><div id="divsigndate<?php echo $i;?>" class="dausecnewsecrd2">
				<?php if(!empty($rs[$i]->signdate)){
					echo date("d/m/Y", strtotime($rs[$i]->signdate));
					echo '<br/>';
					echo '<span class="brtimeclass">'.date("h:i A", strtotime($rs[$i]->signdate)).'</span>';
					
				}?></div>
							
					<div id="divsigndocssending<?php echo $i;?>" style="display:none;"></div>
				</td>
			 <td class="daupsec025b"><div id="divsigndate-1<?php echo $i;?>" class="dausecnewsecrd2">
				<?php if(!empty($rs[$i]->signdate)){
					if(!empty($rs[$i]->docusign2)){
				
				?>
					<a href="<?php echo $rs[$i]->docusign2;?>" target="_blank"><h4>Certificate</h4></a>
				<?php } 
				}?></div>
				<?php if(!empty($rs[$i]->docusignorgs)){?>
					<div id="divsigndocs<?php echo $i;?>">	
								<a href="<?php echo $rs[$i]->docusignorgs;?>" target="_blank"><h4>Offre de Prime</h4></a>
								<?php }else if(!empty($rs[$i]->docusign1)){?>
									<a href="<?php echo $rs[$i]->docusign1;?>" target="_blank"><h4>Offre de Prime</h4></a>
								<?php 
								}?>
				
				</div>
				<?php if(empty($rs[$i]->docusign2)){?>
				<div id="divsignuploaddocs<?php echo $i;?>" class="divsecclsssection09">
									<label><img class="digimgbigse <?php echo $uploadocusignorgs;?>" src="<?php echo plugins_url('crmnew/images/img5.png' , dirname(__FILE__));?>">
									<input type="file" name="docsigns<?php echo $i;?>" id="docsigns<?php echo $i;?>" onchange="return upload_sign_attachment('<?php echo $rs[$i]->id;?>','<?php echo $i;?>','docsigns','<?php echo $rs[$i]->file_number;?>','docusignupdatedby','<?php echo plugins_url('crmnew/send_signed_attachments.php' ,dirname(__FILE__));?>');"/>
									</label>
								</div>
					<?php }?>	
				
				
				</td>	
</tr>
</tbody>
</table>



</td>



<!------3col add end-->


		
				
				
				

				<td class="daupsec026">
				<div id="divsdatedoc8<?php echo $i;?>" class="divsecclsssection08" style="display:none;"><?php if(!empty($rs[$i]->document8senddate)){
							echo date('d/m/Y',strtotime($rs[$i]->document8senddate));}?>
				</div>	
				
				
				<div id="divsdoc8<?php echo $i;?>" class="divsecclsssection01">
								<div id="divsdocdoc8<?php echo $i;?>" class="divsecclsssection1">
								<?php if(!empty($rs[$i]->document8)){?>
									<a href="<?php echo $rs[$i]->document8;?>" target="_blank"><h4><img class="greentickmark" src="<?php echo plugins_url('crmnew/images/img4.png' , dirname(__FILE__));?>"></h4></a>
								<?php }else{?>
									<label> <img src="<?php echo plugins_url('crmnew/images/img5.png' , dirname(__FILE__));?>">
								<input type="file" name="doc8<?php echo $i;?>" id="doc8<?php echo $i;?>" onchange="return upload_attachment('<?php echo $rs[$i]->id;?>','<?php echo $i;?>','doc8','<?php echo $rs[$i]->file_number;?>','document8','<?php echo plugins_url('crmnew/send_attachments.php' ,dirname(__FILE__));?>');"/></label> 

								<?php }?>
								</div>
								<?php if(!empty($rs[$i]->document8)){?>
								<label> <img class="rgtsomimg" src="<?php echo plugins_url('crmnew/images/img5.png' , dirname(__FILE__));?>">
								<input type="file" name="doc8<?php echo $i;?>" id="doc8<?php echo $i;?>" onchange="return upload_attachment('<?php echo $rs[$i]->id;?>','<?php echo $i;?>','doc8','<?php echo $rs[$i]->file_number;?>','document8','<?php echo plugins_url('crmnew/send_attachments.php' ,dirname(__FILE__));?>');"/></label> 
								<?php }?>
								<div id="divssubdoc8<?php echo $i;?>" style="display:none;">
									<label> <img class="rgtsomimg" src="<?php echo plugins_url('crmnew/images/img5.png' , dirname(__FILE__));?>">
								<input type="file" name="doc8<?php echo $i;?>" id="doc8<?php echo $i;?>" onchange="return upload_attachment('<?php echo $rs[$i]->id;?>','<?php echo $i;?>','doc8','<?php echo $rs[$i]->file_number;?>','document8','<?php echo plugins_url('crmnew/send_attachments.php' ,dirname(__FILE__));?>');"/></label> 
								</div>
								</div>
						<div id="divssendingdoc8<?php echo $i;?>" style="display:none;" class="divsecclsssection2"></div>
				
				 </td>
								<td class="daupsec027">
				<div id="divsdatedoc9<?php echo $i;?>" class="divsecclsssection09" style="display:none;"><?php if(!empty($rs[$i]->document9senddate)){
							echo date('d/m/Y',strtotime($rs[$i]->document9senddate));}?>
				</div>	
				
				
				<div id="divsdoc9<?php echo $i;?>" class="divsecclsssection01">
								<div id="divsdocdoc9<?php echo $i;?>" class="divsecclsssection1">
								<?php if(!empty($rs[$i]->document9)){?>
									<a href="<?php echo $rs[$i]->document9;?>" target="_blank"><h4><img class="greentickmark" src="<?php echo plugins_url('crmnew/images/img4.png' , dirname(__FILE__));?>"></h4></a>
								<?php }else{?>
									<label> <img src="<?php echo plugins_url('crmnew/images/img5.png' , dirname(__FILE__));?>">
								<input type="file" name="doc9<?php echo $i;?>" id="doc9<?php echo $i;?>" onchange="return upload_attachment('<?php echo $rs[$i]->id;?>','<?php echo $i;?>','doc9','<?php echo $rs[$i]->file_number;?>','document9','<?php echo plugins_url('crmnew/send_attachments.php' ,dirname(__FILE__));?>');"/></label> 

								<?php }?>
								</div>
								<?php if(!empty($rs[$i]->document9)){?>
								<label> <img  class="rgtsomimg" src="<?php echo plugins_url('crmnew/images/img5.png' , dirname(__FILE__));?>">
								<input type="file" name="doc9<?php echo $i;?>" id="doc9<?php echo $i;?>" onchange="return upload_attachment('<?php echo $rs[$i]->id;?>','<?php echo $i;?>','doc9','<?php echo $rs[$i]->file_number;?>','document9','<?php echo plugins_url('crmnew/send_attachments.php' ,dirname(__FILE__));?>');"/></label> 
								<?php }?>
								<div id="divssubdoc9<?php echo $i;?>" style="display:none;">
									<label> <img class="rgtsomimg" src="<?php echo plugins_url('crmnew/images/img5.png' , dirname(__FILE__));?>">
								<input type="file" name="doc9<?php echo $i;?>" id="doc9<?php echo $i;?>" onchange="return upload_attachment('<?php echo $rs[$i]->id;?>','<?php echo $i;?>','doc9','<?php echo $rs[$i]->file_number;?>','document9','<?php echo plugins_url('crmnew/send_attachments.php' ,dirname(__FILE__));?>');"/></label> 
								</div>
								</div>	
					<div id="divssendingdoc9<?php echo $i;?>" style="display:none;" class="divsecclsssection2"></div>
				 </td>
				<td class="daupsec028 mys28">
				<div id="divsdatedoc10<?php echo $i;?>" class="divsecclsssection010" style="display:none;"><?php if(!empty($rs[$i]->document10senddate)){
							echo date('d/m/Y',strtotime($rs[$i]->document10senddate));}?>
				</div>	
				
				
				<div id="divsdoc10<?php echo $i;?>" class="divsecclsssection01">
								<div id="divsdocdoc10<?php echo $i;?>" class="divsecclsssection1">
								<?php if(!empty($rs[$i]->document10)){?>
									<a href="<?php echo $rs[$i]->document10;?>" target="_blank"><h4><img class="greentickmark" src="<?php echo plugins_url('crmnew/images/img4.png' , dirname(__FILE__));?>"></h4></a>
								<?php }else{?>
									<label> <img src="<?php echo plugins_url('crmnew/images/img5.png' , dirname(__FILE__));?>">
								<input type="file" name="doc10<?php echo $i;?>" id="doc10<?php echo $i;?>" onchange="return upload_attachment('<?php echo $rs[$i]->id;?>','<?php echo $i;?>','doc10','<?php echo $rs[$i]->file_number;?>','document10','<?php echo plugins_url('crmnew/send_attachments.php' ,dirname(__FILE__));?>');"/></label> 

								<?php }?>
								</div>
								<?php if(!empty($rs[$i]->document10)){?>
								<label> <img class="rgtsomimg" src="<?php echo plugins_url('crmnew/images/img5.png' , dirname(__FILE__));?>">
								<input type="file" name="doc10<?php echo $i;?>" id="doc10<?php echo $i;?>" onchange="return upload_attachment('<?php echo $rs[$i]->id;?>','<?php echo $i;?>','doc10','<?php echo $rs[$i]->file_number;?>','document10','<?php echo plugins_url('crmnew/send_attachments.php' ,dirname(__FILE__));?>');"/></label> 
								<?php }?>
								<div id="divssubdoc10<?php echo $i;?>" style="display:none;">
									<label> <img class="rgtsomimg" src="<?php echo plugins_url('crmnew/images/img5.png' , dirname(__FILE__));?>">
								<input type="file" name="doc10<?php echo $i;?>" id="doc10<?php echo $i;?>" onchange="return upload_attachment('<?php echo $rs[$i]->id;?>','<?php echo $i;?>','doc10','<?php echo $rs[$i]->file_number;?>','document10','<?php echo plugins_url('crmnew/send_attachments.php' ,dirname(__FILE__));?>');"/></label> 
								</div>
								</div>	
					<div id="divssendingdoc10<?php echo $i;?>" style="display:none;" class="divsecclsssection2"></div>
				 </td>


				<td class="daupsec029 dauclrsred">€<?php echo number_format($rs[$i]->puprime1,1, ',', ' ');?></td>
				<td class="daupsec030 dauclrsred">€<?php echo number_format($rs[$i]->puprime2,1, ',', ' ');?></td>
				<td class="daupsec031 dauclrsrednew"><div id="prfits<?php echo $ID;?>" class="divsecclsssection022"> <?php 
								if(strstr($rs[$i]->profits,"-")){
									echo "-€".str_replace("-","",number_format($rs[$i]->profits,0," "," "));
								}else{
									echo "€".number_format($rs[$i]->profits,0," "," ");
								}
								?></div></td>


		</tr>
		<?php }
			}else{?>
				<tr><td align="center" colspan="61">No Record found!</td></tr>
			<?php }?>  
		  </tbody>

		</table>
		