<div class="wrap">
	<h2>WP-MoniTee - Configuration</h2>

	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<?php wp_nonce_field('update-options'); ?>

		<table class="form-table">

			<tr valign="top">
				<th scope="row">Titre du Widget</th>
				<td><input id="wpmonitee_options_title" name="wpmonitee_options_title" type="text" value="<?php echo $aOptions[0]; ?>" class="regular-text" /></td>

			</tr>

			<tr valign="top">
				<th scope="row">Serveurs à surveiller</th>
				<td>
					<textarea id="wpmonitee_options_servers" name="wpmonitee_options_servers" cols="35" rows="5"><?php echo $sSrvList; ?></textarea>
					<br />
					<em>Un serveur par ligne. Le format doit être le suivant : [IP ou DNS]:[port].<br />
					Exemples : 84.57.152.6:8303, mon-serveur-teeworlds.com:8304.</em>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row">Temps minimum entre deux requêtes</th>
				<td>
					<input id="wpmonitee_options_time" name="wpmonitee_options_time" type="text" maxlenght="3" value="<?php echo $aOptions[1]; ?>" class="small-text" />
					secondes.<br />
					<em>Les informations des serveurs sont enregistrées dans la base de Wordpress et sont considérées
					comme périmées au dela du temps que vous indiquerez ici. Cela permet d'éviter d'effectuer une
					requête au serveur Teeworlds à chaque fois que le widget est affiché.</em>
				</td>
			</tr>

		</table>

		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="wpmonitee_options_title, wpmonitee_options_servers, wpmonitee_options_time" />

		<p class="submit">
			<input type="submit" name="save-wpmonitee-options" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>

	</form>
</div>
