    <footer>

<?php if ( is_active_sidebar( 'footer-widget-1' ) ) : ?>
    <div class="footer-widget-area">
        <?php dynamic_sidebar( 'footer-widget-1' ); ?>
    </div>
<?php endif; ?>



	<p style="padding-top:4.5rem;text-align:center;">
&copy; <?php echo date('Y'); ?>. All rights reserved. <br>
</p>
    </footer>
    <?php wp_footer(); ?>
</body>
</html>

