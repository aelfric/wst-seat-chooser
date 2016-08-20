<h1>
Hello World
</h1>
<?php
$args = array(
    'post_type' => 'product_variation',
    'posts_per_page' => 12
);
$loop = new WP_Query( $args );

echo "<pre>";
while($loop->have_posts()){
    $p = $loop->next_post();
    $prod = new WC_Product_Variation($p->ID);?>
    <a href="<?php echo $prod->ID; ?>"><?php echo $prod->get_title(); ?></a>
<?php 
}
echo "</pre>";
?>
