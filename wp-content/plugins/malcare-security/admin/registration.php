<div id="content-wrapper" style="width: 99%;">
	<!-- Content HTML goes here -->
	<div class="mui-container-fluid">
		<div class="mui--appbar-height"></div>
		<br><br>
		<div class="mui-row">
			<div style="background: #4686f5; overflow: hidden;">
				<a href="https://www.malcare.com/?utm_source=mc_free_plugin_lp_logo&utm_medium=logo_link&utm_campaign=mc_free_plugin_lp_header&utm_term=header_logo&utm_content=image_link"><img src="<?php echo plugins_url($this->getPluginLogo(), __FILE__); ?>" style="padding: 10px;"></a>
				<div class="top-links">
					<span class="bv-top-button"><a href="https://wordpress.org/support/plugin/malcare-security/reviews/#new-post">Leave a Review</a></span>
					<span class="bv-top-button"><a href="https://wordpress.org/support/plugin/malcare-security/">Need Help?</a></span>
				</div>
			</div>
		</div>
	</div>
	<div class="mui-container-fluid" style="padding: 0px;">
		<div class="mui-col-md-10" style="padding-left: 0px;">
			<br>
			<div class="bv-box" style="padding-top: 10px; padding-bottom: 10px;">
				<?php require_once dirname( __FILE__ ) . "/top_box.php";?>
			</div>
			<div class="mui-panel new-account-panel">
				<form dummy=">" action="<?php echo $this->bvinfo->appUrl(); ?>/home/mc_signup" style="padding-top:10px; margin: 0px;" onsubmit="document.getElementById('get-started').disabled = true;"  method="post" name="signup">
					<div style="width: 800px; margin: 0 auto; padding: 10px;">
					<div class="mui--text-title form-title">Let's scan your website</div>
						<input type='hidden' name='bvsrc' value='wpplugin' />
						<input type='hidden' name='origin' value='protect' />
							<?php echo $this->siteInfoTags(); ?>
						<input type="text" class="bv-input" id="email" name="email" style="width:430px;" value="<?php echo get_option('admin_email');?>" required>
						<select name="purpose" class="bv-input select-purpose" required>
							<option value="" hidden>Looking for?</option>
							<?php
								$signupPurpose = array("Malware Scan", "Malware Clean", "Firewall", "Login Protection", "Others");
								foreach($signupPurpose as $value) {
									echo "<option value='".$value."'>".$value."</option>";
								}
							?>
						</select>
						<button id="get-started" class="mui-btn mui-btn--raised mui-btn--primaryi get-started-button" type="submit" style="background: #4686f5;">Scan Site</button><br/>
						<input type="checkbox" name="consent" value="1"/>I agree to MalCare <a href="https://www.malcare.com/tos" target="_blank" rel="noopener noreferrer">Terms of Service</a> and <a href="https://www.malcare.com/privacy" target="_blank" rel="noopener noreferrer">Privacy Policy</a>
					</div>
				</form>
				<br/>
			</div>
		</div>
		<div class="mui-col-md-2 side">
			<div class="side-box" style="margin: 0px !important;">
				<h2 class="side-box-title">Why choose MalCare ?</h2>
				<strong>
					<ul>
						<li><span class="bv-tick">&#10003;</span> Detects Hidden Malware</li>
						<li><span class="bv-tick">&#10003;</span> Doesn't slowdown website</li>
						<li><span class="bv-tick">&#10003;</span> Never Breaks your site</li>
						<li><span class="bv-tick">&#10003;</span> Malware Removal in &lt; 60s</li>
						<li><span class="bv-tick">&#10003;</span> 24*7 Smart Firewall</li>
						<li><span class="bv-tick">&#10003;</span> Unlimited Malware Removal</li>
					</ul>
				</strong>
			</div>
			<div class="side-box" style="margin-top: 20px; overflow: hidden;">
				<h2 class="side-box-title">What's in MalCare Pro?</h2>
				<strong>
					<ul>
						<li><span class="bv-tick">&#10003;</span> Daily Automatic Scans</li>
						<li><span class="bv-tick">&#10003;</span> 1-Click Malware Removal</li>
						<li><span class="bv-tick">&#10003;</span> Personalized Support</li>
						<li><span class="bv-tick">&#10003;</span> Add Users and Clients</li>
						<li><span class="bv-tick">&#10003;</span> White Label Plugin</li>
						<li><span class="bv-tick">&#10003;</span> Client Reporting</li>
					</ul>
				</strong>
				<div class="bv-upgrade-button"><a href="https://www.malcare.com/pricing/?utm_source=mc_free_plugin_lp_pricing&utm_medium=lp_upgrade&utm_campaign=mc_free_plugin_lp_upgrade&utm_term=upgrade_button&utm_content=button_link">Get Me Pro &raquo;</a></span></div>
			</div>
		</div>
	</div>
</div>

<footer style="width: 99%;">
	<div style="background: #45b3e0; margin-top: 20px; padding-top:10px; padding-bottom: 10px;">
		<div style="width: 671px; margin: 0 auto;">
			<span class="footer-logo" style="color: #FFF; padding: 10px; display: inline-block; font-weight: bold; font-size: 28px; margin-top: 5px; float: left;"> Trusted By </span>
			<span class="footer-logo"><img src="<?php echo plugins_url("/../img/adobe-logo.png", __FILE__); ?>" style="height: 36px; margin-left: 30px;"/></span>
			<span class="footer-logo"><img src="<?php echo plugins_url("/../img/intel-logo.png", __FILE__); ?>" style="height: 38px;" /></span>
			<span class="footer-logo"><img src="<?php echo plugins_url("/../img/sap-logo.png", __FILE__); ?>" style="height: 32px;" /></span>
			<span><img src="<?php echo plugins_url("/../img/valet-logo.png", __FILE__); ?>" style="height: 42px;" /></span>
		</div>
	</div>
	<div class="mui-container mui--text-center" style="margin-top: 10px;">
	Made with ♥ by <a href="https://blogvault.net"><img src="<?php echo plugins_url('../img/bv.png', __FILE__); ?>" /></a>
	</div>
</footer>