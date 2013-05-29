<?php
include_once('include/globals.php');
include_once('include/functions.php');

/*
 * How it should work:
 *
 * Ctrl-cmd-enter:
 *	|| Crooked Teeth
 		Plans by Death Cab for Cutie ⭑⭑⭑⭒⭒
 	Plans
 		More from this album
 	Death Cab for Cutie
 		More by this artist
 	Search for music
 		Search Spotify by artist, album, or track
 		
 * Ctrl-cmd-enter, some chars / "artist" "album" "track":
 	Search for artist '{query}'...
 		Narrow this search to artists
 	Search for album '{query}'...
 		Narrow this search to albums
 	Search for track '{query}'...
 		Narrow this search to tracks
 	Continue typing to search...
 		Search artists, albums, and tracks combined
 		
 * Ctrl-cmd-enter, type 3 letters:
 	Begin searching
 	
 	Action an artist: search for that artist
 	Action an album: search for that album
 	
 	Command action: open in Spotify
 	
 * spot artist ♩
 * spot album ♩
 * spot track ♩
 */

# thanks to http://www.alfredforum.com/topic/1788-prevent-flash-of-no-result
$query = iconv("UTF-8-MAC", "UTF-8", $query);
mb_internal_encoding("UTF-8");
$chars = mb_strlen($query);
$i = 0;

if($chars == 1 && mb_stristr('npts', $query)) {

	$currentTrack = spotifyQuery("name of current track");
	$currentStatus = (spotifyQuery("player state") == 'playing') ? '►' : '❙❙';
	$currentAlbum = spotifyQuery("album of current track");
	$currentArtist = spotifyQuery("artist of current track");

	switch(mb_strtolower($query)){
		case 'n':
			$results[$i][title] = "Next Track";
			$results[$i][subtitle] = "$currentStatus $currentTrack";
			$results[$i][arg] = "next track";
			$i++;
			break;
		case 'p':
			$results[$i][title] = "Previous Track";
			$results[$i][subtitle] = "$currentStatus $currentTrack";
			$results[$i][arg] = "previous track";
			$i++;
			break;
		case 't':
			$results[$i][title] = "$currentStatus $currentTrack";
			$results[$i][subtitle] = "$currentAlbum by $currentArtist";
			$results[$i][arg] = "playpause";
			$i++;
			break;
		case 's':
			$results[$i][title] = "$currentStatus $currentTrack";
			$results[$i][subtitle] = "$currentAlbum by $currentArtist";
			$results[$i][arg] = "pause";
			$i++;
			break;
	}

} else if($chars < 3) {

	$currentTrack = spotifyQuery("name of current track");
	$currentStatus = (spotifyQuery("player state") == 'playing') ? '►' : '❙❙';
	$currentAlbum = spotifyQuery("album of current track");
	$currentArtist = spotifyQuery("artist of current track");
	$currentArtistArtwork = getArtistArtwork($currentArtist);
	$currentURL = spotifyQuery("spotify url of current track");
	$currentArtwork = getTrackArtwork($currentURL);

	$results[$i][title] = "$currentStatus $currentTrack";
	$results[$i][subtitle] = "$currentAlbum by $currentArtist";
	$results[$i][arg] = "playpause";
	$i++;
	$results[$i][title] = "$currentAlbum";
	$results[$i][subtitle] = "More from this album...";
	$results[$i][autocomplete] = "$currentAlbum";
	$results[$i][valid] = "no";
	$results[$i][icon] = (!file_exists($currentArtwork)) ? 'icon.png' : $currentArtwork;
	$i++;
	$results[$i][title] = $currentArtist;
	$results[$i][subtitle] = "More by this artist...";
	$results[$i][autocomplete] = "$currentArtist";
	$results[$i][valid] = "no";
	$results[$i][icon] = (!file_exists($currentArtistArtwork)) ? 'icon.png' : $currentArtistArtwork;
	$i++;
	$results[$i][title] = "Search for music...";
	$results[$i][subtitle] = "Begin typing to search";
	$results[$i][valid] = 'no';

} else {
	foreach (array('track','artist','album') as $type) {
		$json = fetch("http://ws.spotify.com/search/1/$type.json?q=" . str_replace("%3A", ":", urlencode($query)));
		
		if(empty($json))
			continue;
		
		$json = json_decode($json);
		
		$currentResultNumber = 1;
		foreach ($json->{$type . "s"} as $key => $value) {
			if($currentResultNumber > $maxResults / 3)
				continue;
			
			// Figure out search rank
			$popularity = $value->popularity;
			
			if($type == 'artist') {
				$popularity+= .5;
			}
			
			// Convert popularity to stars
			$stars = floor($popularity * 5);
			$starString = str_repeat("⭑", $stars) . str_repeat("⭒", 5 - $stars);
				
			if($type == 'track') {
				$subtitle = $value->album->name . " by " . $value->artists[0]->name;
			} elseif($type == 'album') {
				$subtitle = "Album by " . $value->artists[0]->name;
			} else {
				$subtitle = ucfirst($type);
			}
			
			$subtitle = "$starString $subtitle";
						
			$currentResult[uid] = "bs-spotify-$query-$type";
			$currentResult[arg] = 'activate (open location "' . $value->href . '")';
			$currentResult[title] = $value->name;
			$currentResult[subtitle] = $subtitle;
			$currentResult[popularity] = $popularity;
			if($show_images)
				$currentResult[icon] = getTrackArtwork($value->href);
			
			$results[] = $currentResult;
			
			$currentResultNumber++;
		}
	}
	
	if(!empty($results))
		usort($results, "popularitySort");
}

alfredify($results);

?>