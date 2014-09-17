<?php

/**
 * dieses Skript bildet die RPC-Schnittstelle,
 * über die die AJAX-Abfragen laufen
 */

require ( 'lib_sqlitedb.php' );
require ( 'lib_bechdel.php' );
require ( 'lib_html.php' );
require ( 'lib_imdb.php' );

$db = new sqlitedb();

/**
 * Filmliste basierend auf REQUEST-Parametern zurückgeben
 *
 * @param Array $p REQUEST-Parameter
 * @return String HTML-Code der Filmliste
 */
function rpc_filter ( $p )
{
	global $db;

	$movies = $db -> getMovieList ( $db -> getFilters ( $p ) );

	$html = '';

	foreach ( $movies as $movie )
		$html .= getMovieSnippet ( $movie );

	return array ( 'count' => count ( $movies ),
				   'html'  => $html );
}

if ( !empty ( $_REQUEST [ 'act' ] ) ) switch ( $_REQUEST [ 'act' ] )
{
	case 'add_movie':
		$imdb_id = intval ( $_REQUEST [ 'imdb_id' ] );

		if (    empty ( $imdb_id )
			 || empty ( $_REQUEST [ 'custom' ][ 'languages' ] ) )
		{
			$return = getEditForm();
			break;
		}
		else
		{
			$movie = getMovie ( $imdb_id );

			// Bechdel-Daten ergänzen
			if ( false !== ( $bechdel_info = getBechdelInfo ( $imdb_id ) ) )
				$movie [ 'bechdel' ] = $bechdel_info;

			insertMovie ( $movie );
		}

		// kein break...

	case 'save_movie':
		$imdb_id = intval ( $_REQUEST [ 'imdb_id' ] );

		if ( !empty ( $imdb_id ) )
			updateMovie ( $imdb_id,
						  array ( 'custom' => $_REQUEST [ 'custom' ] ) );

		// kein break...

	case 'update_imdb':

		// Extra-IF, weil das hier sonst auch in den beiden bisherigen
		// Fällen ausgeführt wird (da kein break)

		if ( $_REQUEST [ 'act' ] == 'update_imdb' )
		{
			$imdb_id = intval ( $_REQUEST [ 'imdb_id' ] );

			$movie = getMovie ( $imdb_id );

			updateMovie ( $imdb_id,
						  array ( 'imdb' => $movie [ 'imdb' ] ) );
		}

		// kein break...

	case 'details':
		$return = getMovieDetails ( $_REQUEST [ 'imdb_id' ] );
		break;

	case 'add':
		$return = getEditForm ( $_REQUEST [ 'imdb_id' ], false );
		break;

	case 'edit':
		$return = getEditForm ( $_REQUEST [ 'imdb_id' ], true );
		break;
}
else
	$return = rpc_filter ( $_REQUEST );

if ( !empty ( $return ) )
{
	header ( 'Content-Encoding: deflate' );

	if ( is_array ( $return ) )
		echo substr ( gzcompress ( json_encode ( $return ) ), 2 );
	else
		echo substr ( gzcompress ( $return ), 2 );
}