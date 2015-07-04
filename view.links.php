<div>

	<ul>
		<?php foreach($links as $link) { ?>
			<ol><a href="/<?php echo $settings['slug']; ?><?php echo $link['href']; ?>"><?php echo $link['text']; ?></a></ol>
		<?php } ?>
	</ul>

	<?php include('subview.footer.php'); ?>
</div>