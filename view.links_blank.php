<div>

	<h2>Services</h2>
	<ul>
		<?php foreach($term_links as $link) { ?>
			<ol><a href="/<?php echo $settings['slug']; ?><?php echo $link['href']; ?>"><?php echo $link['text']; ?></a></ol>
		<?php } ?>
	</ul>

	<h2>Locations</h2>
	<ul>
		<?php foreach($location_links as $link) { ?>
			<ol><a href="/<?php echo $settings['slug']; ?><?php echo $link['href']; ?>"><?php echo $link['text']; ?></a></ol>
		<?php } ?>
	</ul>

	<?php include('subview.footer.php'); ?>
</div>