<table width=600 style="color:#333;font-family: 'oxygen', arial;margin:10px;">
	<tr>
		<td style="border-bottom: 1px solid #cccccc; padding-bottom: 25px">
			<img src="<?php echo get_site_url().'/wp-content/uploads/2014/01/logo-base.png';?>" alt="logo" />
		</td>
	</tr>

	<tr>
		<td>
			<h4 style="color:#d63136; margin-top:25px;">Bine ați venit in magazinul <?php echo get_bloginfo('name');?>!</h4>
		</td>
	</tr>
	<tr>
		<td>
			 <p>
				Începând din acest moment aveți acces în platforma noastra de comenzi online.
				În acest fel aveți la dizpoziție în permanență informații despre stoc și preț.
				Prețurile care apar în dreptul produselor conțin disconturile de care dispuneți prin contractul încheiat cu noi.
				în urma comenzilor plasate veți primi e-mail de confirmare.
			</p>
			<p>
				Dacă aveți nelămuriri vă rugăm să contactați Account Manager-ul Dvs.
			</p>
		</td>
	</tr>
	<tr>
		<td>
			 <p>Pentru a vă autentifica pe site-ul nostru, dați click pe <a href="<?php echo wp_login_url() ;?>" style="color:#d63136">logare</a>, iar dupa acest pas introduceti adresa de e-mail și parola.
			</p>
		</td>
	</tr>

	<tr>
		<td>
			<h4 style="color:#d63136"></br>
					Datele de autentificare:
			</h4>
			<p><strong>Nume utilizator / email:</strong> <?php echo $customer['email']; ?></p>
			<p><strong>Parola:</strong> <?php echo $password; ?></p>
			<a href="<?php echo wp_login_url(); ?>" style="color:white; background-color:#d63136; padding:10px 25px;text-decoration: none;margin-top:25px; border-radius: 4px; display: block;width: 135px;">Autentificare client</a>
		</td>
	</tr>

</table>