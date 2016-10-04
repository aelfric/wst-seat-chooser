<div class="wrap">
    <h2>WST Seat Chooser</h2>
    <form method="post" action="options.php"> 
    <?php @settings_fields('wst_seat_chooser-group'); ?>
    <?php @do_settings_fields('wst_seat_chooser-group'); ?>
    <table class="form-table">  
        <tr valign="top">
            <th scope="row"><label for="setting_a">Seating Chart</label></th>
            <td><textarea name="seating_chart" id="seating_chart"><?php echo get_option('seating_chart'); ?></textarea></td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="reserved_seats">Reserved Seats</label></th>
            <td><input type="text" name="reserved_seats" id="reserved_seats" value="<?php echo get_option('reserved_seats'); ?>" /></td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="category_name">Product Category</label></th>
            <td><input type="text" name="category_name" id="category_name" value="<?php echo get_option('category_name'); ?>" /></td>
        </tr>
        <?php @submit_button(); ?>
    </form>
</div>
