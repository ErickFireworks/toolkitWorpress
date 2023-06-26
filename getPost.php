<?php 

 function get_categories_by_options() {

	$categories = get_categories( [
		'orderby' => 'name',
		'order'   => 'ASC',
		'hide_empty' => FALSE,
	] );

	$categories_info = [];

	foreach( $categories as $index => $category ) {

		$categories_info[ $index ] = [
			"ID" 	=> $category -> cat_ID,
			"name" 	=> $category -> name,
			"slug" 	=> $category -> slug,
		];
	}

	return $categories_info;

} //end function
	
function get_complement_publication ( $post ) {

	//if( is_null( $post ) )
	//throw new Exception( 'The parameters are not correct.' );

	if( isset( $post ) && !empty( $post ) ) {
		$author_id = get_post_field( 'post_author', $post->ID );
		$author_name = get_the_author_meta( 'display_name', $author_id );

		$store = ( object ) [
			'author' => $author_name,
			'images' => ( object ) [
				'full'		=>	wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' )[ 0 ],
				'large'		=>	wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'large' )[ 0 ],
				'medium'	=>	wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'medium' )[ 0 ],
				'medium_large'	=>	wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'medium_large' )[ 0 ],
				'thumbnail'	=>	wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'thumbnail' )[ 0 ],	
			],
			'content' => trim( strip_tags( $post -> post_content ) ),
			'extract' => substr( trim( strip_tags( $post -> post_content ) ), 0, 90 ) . ' ...',
		];
	} //end if
	else
		$store = ( object ) [];

	return $store;

} // end function

function get_best_publications_by_category() {
	
	$categories_b4i = get_categories_by_options();
	$store = [];
	
	foreach($categories_b4i as $index => $category_b4i){
		
		$publications = get_publications_for_category([ 'id_category' => $category_b4i["ID"], 'numberposts' => 1  ]);
		
		if( $publications )
			$store [$index] = $publications;
			
	} //end foreach
	unset( $index, $category_b4i );
	
	return $store;
	
} //end function
	
function get_publications_for( array $args = NULL ) {
	
	if( is_null( $args ) )
		throw new Exception( 'The parameters are not correct.' );
	
	$posts = new WP_Query( $args );
	
	if( $posts ) {
		$posts = $posts -> posts;
		//$posts = $posts[0];
		//$posts -> data = get_complement_publication( $posts );
	}
	else 
		$posts = NULL;

	return $posts;
	
} //end function
	
function get_publications_for_category( array $params = NULL ) {

	if( is_null( $params ) )
		throw new Exception( 'The parameters are not correct.' );

	$args = [
		'category'		=> $params[ 'id_category' ] ?? 1,
		'numberposts'	=> $params[ 'numberposts' ] ?? -1,
		'order'			=> $params[ 'order' ] ?? 'DESC',
	];

	$posts = get_posts( $args );

	if( $posts ) {
		$posts = $posts[0];
		$posts -> data = get_complement_publication( $posts );
	}
	else 
		$posts = NULL;

	return $posts;

} //end function
	
function get_category_by_post( int $id_post = NULL ){

	$category_detail = get_the_category( $id_post ); //$post->ID
	$category_detail = $category_detail[0];

	return [
		"ID" 	=> $category_detail -> cat_ID,
		"name" 	=> $category_detail -> name,
		"slug" 	=> $category_detail -> slug,
	];

} //end function
	
function get_post_id_by_slug($slug) {	

	/*TODO: FALTA EXTRAER LA URL PARA QUE SE PUEDA USAR ESTA OPCIÓN*/

	$args = [
		'posts_per_page' => 1,
		'post_name__in'  => [$slug]
	];

	return get_posts( $args )[0] -> ID;

} // end function

//ShortCode Function Custom

function wpb_demo_shortcode() { 
 
	$categories_b4i = get_categories_by_options();
	$item_slide = '';
	
	foreach($categories_b4i as $category_b4i) {
		
		$publications = get_publications_for_category([ 'id_category' => $category_b4i["ID"], 'numberposts' => 1  ])  ?? NULL;
		
		if( $publications )
			$item_slide .= "<div class='item-main-carousel'>
							<div class='item-slider-custom' style='background-image: url(". $publications -> data -> images -> large .");'>
								<div class='item-slider-content' >
									<a class='slider-link' href='/".$publications -> post_name ."'><h3>". $publications -> post_title ."</h3></a>
									<br />
									<p>". $publications -> data -> extract ."</p>
								</div>
							</div>
						</div>";
			
	} //end foreach
	unset( $category_b4i );
	
	$message = "<div class='slider-custom'>". $item_slide ."</div>";
	
	// Output needs to be return
	return $message;
} //end function

function wpb_card_post_general_shortcode() { 
		
	$result = '';
	
	/*Los ultimos 10 post*/
	$args = [
		'post_type'			=> 'post',
		//'orderby'   		=> 'ID',
		'post_status' 		=> 'publish',
		'order'    			=> 'DESC',
		'posts_per_page'	=> 10 // this will retrive all the post that is published 
	];
	
	$posts = get_publications_for( $args );
	$array_posts_no_repeat = [];

	/*Los ultimos post publicados de cada categoría*/
	
	$results_last_publication = get_best_publications_by_category();
	$array_last_publication_id = [];
	
	foreach( $results_last_publication as $index => $result_publication ) {
		
		$array_last_publication_id[$index] = $result_publication -> ID;
	}
	unset( $result_publication, $index );
	
	/*Post no repetidos*/
	
	$count = 0;
	
	foreach( $posts as $index => &$post ){
		$categories = $post ->post_category;
		$categories_b4i = get_categories_by_options();
		if( ! in_array( $post -> ID , $array_last_publication_id ) ){
			
			$post -> data = get_complement_publication( $post );
			$array_posts_no_repeat[$count] = $post;
			
			//var_dump( $post );
			$first = '';
			if($count == 0) {
				$first = ' first';
			}else {
				$first = '';
			}
			$result .= "<div class='card-post-b4i". $first ."' data=''>
						 <figure class='image-post-b4i' style='background-image: url(". $post -> data -> images ->  large .")'>
						 </figure>
						 <hr />
						 <div class='card-body'>
						 	<div class='post-category'><a class='category-link' href=".get_category_link($categories[0]).">". get_cat_name($categories[0]) ."</a></div>
						 	<div class='title-space'>
							 <h5 class='card-title'><a class='link-title-post' href='/". $post -> post_name ."'>". $post -> post_title ."</a></h5>
							</div>
							<p class='card-text'>". $post ->  data -> extract ."</p>
						</div>
					</div>";
			$count++;
		} //end if
	}
	unset( $post, $index );
	
	return "<div class='container-cards-post'>" . $result . "</div>";
	
} //end function

function wpb_card_post_recommended_shortcode() {
	
	$categories_b4i = get_categories_by_options();
	$result = '';
	
	foreach($categories_b4i as $category_b4i) {
		
		$post = get_publications_for_category([ 'id_category' => $category_b4i["ID"], 'numberposts' => 1  ])  ?? NULL;
		
		if( $post )
			$result .= "<div class='most-viewed-card' data=''>
						 <figure class='sidebar-image' style='background-image: url(". $post -> data -> images ->  medium .")'>
						 </figure> 
						
						 <div class='card-body'>
						 	<div class='title-space'>
							 <h5 class='card-title'><a class='link-title-post' href='/". $post -> post_name ."'>". $post -> post_title ."</a></h5>
							</div>
							<p class='card-text'>". $post ->  data -> extract ."</p>
						</div>
						</div>";
			
	} //end foreach
	unset( $category_b4i );
	
	return "<h4 class='text-center'><b>Lo más visto</b></h4>
			<hr>
			<div class='container-cards-post'>" . $result . "</div>";
} //end function

function wpb_card_post_by_category_shortcode() { 
		
	$url_post = trim( trim( "{$_SERVER['REQUEST_URI']}", "/" ) );
	$id_current_post = get_post_id_by_slug( $url_post );
	$category_by_url = get_category_by_post( $id_current_post );
		
	$result = '';
	
	/*todos los post de la categoría*/
	$args = [
		'post_type'			=> 'post',
		'cat'          => $category_by_url["ID"],
		'post_status' 		=> 'publish',
		'order'    			=> 'DESC',
		'posts_per_page'	=> -1 // this will retrive all the post that is published 
	];
	
	$posts = get_publications_for( $args );
	$array_posts_no_repeat = [];

	/*Los ultimos post publicados de cada categoría*/
	
	$results_last_publication = get_best_publications_by_category();
	$array_last_publication_id = [];
	
	foreach( $results_last_publication as $index => $result_publication ) {
		
		$array_last_publication_id[$index] = $result_publication -> ID;
	}
	unset( $result_publication, $index );
	
	/*Agrega ID de la publicaión actual para que no se muestre en relacionados*/
	array_push( $array_last_publication_id, $id_current_post );
	
	/*Post no repetidos*/
	
	$count = 0;
	
	foreach( $posts as $index => &$post ){
		
		if( ! in_array( $post -> ID , $array_last_publication_id ) ){
			
			$post -> data = get_complement_publication( $post );
			$array_posts_no_repeat[$count] = $post;
						
			$result .= "<div class='card-post-b4i card-post-b4i-related' data=''>
						 <figure class='image-post-b4i' style='background-image: url(". $post -> data -> images ->  medium .")'>
						 </figure>
						 <hr />
						 <div class='card-body'>
						 	<div class='title-space'>
							 <h5 class='card-title'><a class='link-title-post' href='/". $post -> post_name ."'>". $post -> post_title ."</a></h5>
							</div>
							<p class='card-text'>". $post ->  data -> extract ."</p>
							<br />
						</div>
					</div>";
			$count++;
		} //end if
	}
	unset( $post, $index );
	
	return "<div class='container-cards-post'>" . $result . "</div>";
	
} //end function

function wpb_article_post_by_category_shortcode() { 
		
	$url_post = trim( trim( "{$_SERVER['REQUEST_URI']}", "/" ) );
	$id_current_post = get_post_id_by_slug( $url_post );
	$category_by_url = get_category_by_post( $id_current_post );
		
	$result = '';
	
	/*todos los post de la categoría*/
	$args = [
		'post_type'			=> 'post',
		'cat'          => $category_by_url["ID"],
		'post_status' 		=> 'publish',
		'order'    			=> 'DESC',
		'posts_per_page'	=> -1 // this will retrive all the post that is published 
	];
	
	$posts = get_publications_for( $args );
	$array_posts_no_repeat = [];

	/*Los ultimos post publicados de cada categoría*/
	
	$results_last_publication = get_best_publications_by_category();
	$array_last_publication_id = [];
	
	foreach( $results_last_publication as $index => $result_publication ) {
		
		$array_last_publication_id[$index] = $result_publication -> ID;
	}
	unset( $result_publication, $index );
	
	/*Agrega ID de la publicaión actual para que no se muestre en relacionados*/
	array_push( $array_last_publication_id, $id_current_post );
	
	/*Post no repetidos*/
	
	$count = 0;
	
	foreach( $posts as $index => &$post ){
		
		if( ! in_array( $post -> ID , $array_last_publication_id ) && $count<3 ){
			
			$post -> data = get_complement_publication( $post );
			$array_posts_no_repeat[$count] = $post;
						
			$result .= "<div class='card-post-b4i card-post-b4i-related' data=''>
						 <figure class='image-post-b4i' style='background-image: url(". $post -> data -> images ->  medium .")'>
						 </figure>
						 <hr />
						 <div class='card-body'>
						 	<div class='title-space'>
							 <h5 class='card-title'><a class='link-title-post' href='/". $post -> post_name ."'>". $post -> post_title ."</a></h5>
							</div>
							<p class='card-text'>". $post ->  data -> extract ."</p>
							<br />
						</div>
					</div>";
			$count++;
		} //end if
	}
	unset( $post, $index );
	
	return "<div class='container-cards-post'>" . $result . "</div>";
	
} //end function

function wpb_all_category_posts_shortcode() { 
		
	
	$result = '';
	
	/*Los ultimos 10 post*/
	$args = [
		'post_type'			=> 'post',
		//'orderby'   		=> 'ID',
		'post_status' 		=> 'publish',
		'order'    			=> 'DESC',
		'posts_per_page'	=> 10 // this will retrive all the post that is published 
	];
	
	$posts = get_publications_for( $args );
	$array_posts_no_repeat = [];

	/*Los ultimos post publicados de cada categoría*/
	
	$results_last_publication = get_best_publications_by_category();
	$array_last_publication_id = [];
	
	foreach( $results_last_publication as $index => $result_publication ) {
		
		$array_last_publication_id[$index] = $result_publication -> ID;
	}
	unset( $result_publication, $index );
	
	/*Post no repetidos*/
	
	$count = 0;
	
	foreach( $posts as $index => &$post ){
		$categories = $post ->post_category;
		$categories_b4i = get_categories_by_options();
		if( ! in_array( $post -> ID , $array_last_publication_id ) ){
			
			$post -> data = get_complement_publication( $post );
			$array_posts_no_repeat[$count] = $post;
			
			//var_dump( $post );
			$first = '';
			if($count == 0) {
				$first = ' first';
			}else {
				$first = '';
			}
			$result .= "<div class='card-post-b4i". $first ."' data=''>
						 <figure class='image-post-b4i' style='background-image: url(". $post -> data -> images ->  large .")'>
						 </figure>
						 <hr />
						 <div class='card-body'>
						 	<div class='post-category'><a class='category-link' href=".get_category_link($categories[0]).">". get_cat_name($categories[0]) ."</a></div>
						 	<div class='title-space'>
							 <h5 class='card-title'><a class='link-title-post' href='/". $post -> post_name ."'>". $post -> post_title ."</a></h5>
							</div>
							<p class='card-text'>". $post ->  data -> extract ."</p>
						</div>
					</div>";
			$count++;
		} //end if
	}
	unset( $post, $index );
	
	return "<div class='container-cards-post'>" . $result . "</div>";
	
} //end function


// register shortcode
add_shortcode('sliderB4I', 'wpb_demo_shortcode'); 

add_shortcode('card_general', 'wpb_card_post_general_shortcode'); 

add_shortcode('card_recommended', 'wpb_card_post_recommended_shortcode'); 

add_shortcode('card_by_category', 'wpb_card_post_by_category_shortcode'); 

add_shortcode('card_by_category_for_post', 'wpb_article_post_by_category_shortcode'); 

add_shortcode('all_category_posts', 'wpb_all_category_posts_shortcode'); 