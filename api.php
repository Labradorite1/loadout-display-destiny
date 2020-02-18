<!DOCTYPE html>
<html>
<head>
	<title></title>
	<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
	<header>
		<form action="api.php" method="post">
			<div class="input-container">	
				<input class="idInput" type="text" name="idInput" id="idInput">
				<input class="submitButton" type="submit" name="submitButton" value=">">
			</div>
			<div class="button-container">
				<div class="one-button-container">
					<label for="xboxInput">Xbox:</label>
					<input type="radio" name="platformInput" value="1" id="xboxInput">
				</div>
				<div class="one-button-container">
					<label for="psInput"> Playstation:</label>
						<input type="radio" name="platformInput" value="2" id="psInput">
				</div>
				<div class="one-button-container">
					<label for="steamInput">Steam:</label>
					<input type="radio" name="platformInput" value="3" id="steamInput">
				</div>
				<div class="one-button-container">
					<label for="stadiaInput">Stadia:</label>
					<input type="radio" name="platformInput" value="5" id="stadiaInput">
				</div>
			</div>
		</form>		
	</header>
	<main>
		<?php
			//46116860184 bungie id prefix //4611686018467615099 my bungie id // 76561198 steam id prefix //76561198 149250601 my steam id 
			ini_set('memory_limit', '1G'); //set max variable size to 1gb
			set_time_limit(2000);

			$playersClass = '"' . "loadout-container" . '"';
			$playerClass = '"' . "player-container" . '"';
			$itemsClass = '"' . "items-container" . '"';
			$itemClass ='"' . "item-container" . '"';
			$subclassClass = '"' . "subclass-image-container" . '"';
			$nameClass = '"'. "playername-container" . '"';
			$playerInfoClass = '"' . "player-info-container" . '"';
			$imagePlaceholderClass = '"' . 'image-placeholder' . '"';
			

			if(isset($_POST["idInput"])) //check if "idInput is set"
			{
				$apiKey = ' '; // API key for bungie.net api
				$idInput = $_POST["idInput"]; //set variable idInput

				if(isset($_POST['platformInput']))
				{
					$platformInput = $_POST["platformInput"]; //set variable platformInput
				}
				else
				{
					$platformInput = -1;
				}//end of isset platform input

				if(is_numeric($idInput))
				{
					$idNumeric = (int)$idInput;
					if($idNumeric > 4611686018400000000 && $idNumeric < 4611686018500000000 )  // check if its a bungie membership id
					{
						$requestId = $idNumeric;
					}
					elseif($idNumeric > 76561198000000 && $idNumeric < 76561199000000000) //check if its a steam id
					{
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, "https://www.bungie.net/Platform/User/GetMembershipFromHardLinkedCredential/SteamId/$idInput/");
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-API-Key: ' . $apiKey));
						$responseBySteamId = json_decode(curl_exec($ch));

						if($responseBySteamId->ErrorCode != 1)
						{
							echo "No user found.";
							exit;
						}
						$requestId = $responseBySteamId->Response->membershipId;
						$requestPlatform = $responseBySteamId->Response->membershipType;
					}
					else
					{
						echo "<h3>This is not a valid ID</h3>";
						exit;
					}
				}
				else
				{
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, "https://www.bungie.net/platform/Destiny2/SearchDestinyPlayer/$platformInput/$idInput/");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-API-Key: ' . $apiKey));
					$requestByUsername = json_decode(curl_exec($ch));

					if(count($requestByUsername->Response) > 1)
					{
						echo "<div class=$playersClass>";
						foreach($requestByUsername->Response as $user)
						{
							echo "<div class=$playerClass>";
							echo "<div class=$nameClass>";
							echo "<h2>$user->displayName";
							echo "</div>";
							echo "<div class=$playerInfoClass>";
							echo "<h3>Membership Id: $user->membershipId";
							echo "<h3>Membership Type: $user->membershipType</h3>";
							echo "</div>";
							echo "</div>";
						}
						echo "</div>";
					}
					elseif(count($requestByUsername->Response) == 0)
					{
						echo "<h3>No user found.</h3>";
						exit;
					}
					else
					{
						$requestId = $requestByUsername->Response[0]->membershipId;
						$requestPlatform = $requestByUsername->Response[0]->membershipType;
					}
				}

				if(isset($requestId) && isset($requestPlatform))
				{

				}
				else
				{
					exit;
				}
				////////////////////////////////
				//CREDENTIAL VERIFICATION DONE//
				////////////////////////////////

				// $inventoryManifestRaw = file_get_contents('https://www.bungie.net/common/destiny2_content/json/en/DestinyInventoryItemDefinition-2fbe1829-dfcd-44ec-84d3-bb04a3777dc1.json');
				// $inventoryManifest = json_decode($inventoryManifestRaw); //convert the manifest into a json manifest

				$dbHost = 'localhost';
				$dbName = 'destiny_temp2';
				$dbUser = 'root';
				$dbPassword = '';

				$db = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPassword);

				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

				$ch = curl_init(); //get the party members of the user
				curl_setopt($ch, CURLOPT_URL, "https://www.bungie.net/platform/Destiny2/$requestPlatform/Profile/$requestId/?components=1000");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-API-Key: ' . $apiKey));
				$startingPlayer = json_decode(curl_exec($ch));

				if($startingPlayer->ErrorCode == 1) //check if the bungie api is working properly and if the membership id was correct
				{
					$notOnline = false;
					$playerIds = [];
					if(property_exists($startingPlayer->Response->profileTransitoryData, 'data')) //check if the account is online
					{ //if they are online
						foreach($startingPlayer->Response->profileTransitoryData->data->partyMembers as $partyMember) //add each membership id to the list
						{
							array_push($playerIds, $partyMember->membershipId);
						}
					}
					else
					{
						array_push($playerIds, $requestId);
						$notOnline = true;
					}

					$divClass = '"' . "loadout-container" . '"'; //create the loadout container (big grid that contains each player, 2 columns per row)
					echo "<div class=$divClass>";
					
					$playerCount = 0;
						foreach($playerIds as $playerId) //for every player id in the list
						{
							$membershipType = $requestPlatform;

							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, "https://www.bungie.net/platform/Destiny2/$membershipType/Profile/$playerId/?components=100,200");
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-API-Key: ' . $apiKey));
							$player = json_decode(curl_exec($ch));

							if($player->ErrorCode != 1)
							{
								for($i = 1; $i != 6; $i++) //iterate through the different membershiptypes until it finds the right one
								{
									$ch = curl_init();
									curl_setopt($ch, CURLOPT_URL, "https://www.bungie.net/platform/Destiny2/$i/Profile/$playerId/?components=100,200");
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
									curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-API-Key: ' . $apiKey));
									$player = json_decode(curl_exec($ch));
									if($player->ErrorCode == 1)
									{
										$membershipType = $i;
										$i = 5;
									}
								}
							}
							$dateLastPlayedPlayer = $player->Response->profile->data->dateLastPlayed; //put the date last played of the entire account in the variable
							

							foreach($player->Response->profile->data->characterIds as $characterId) //for every character in the account
							{
								if($player->Response->characters->data->$characterId->dateLastPlayed >= $dateLastPlayedPlayer || $notOnline == true) //check if the characters date last played is the same or higher than the accounts to figure out which character the player is playing on
								{
									$playerClassName = '"' . "player-container" . '"';	//player container div, div that takes up one slot in the loadout container slot
									echo "<div class=$playerClassName>";

									$ch = curl_init(); //request the inventory data for the selected character
									curl_setopt($ch, CURLOPT_URL, "https://www.bungie.net/platform/Destiny2/$membershipType/Profile/$playerId/Character/$characterId/?components=200,205,300,306");
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
									curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-API-Key: ' . $apiKey));
									$characterInventoryData = json_decode(curl_exec($ch));
									
									$subclassNodeId	 = 11; //11 = the node id for the subclass

									$itemInstanceIdSubclass = $characterInventoryData->Response->equipment->data->items[$subclassNodeId]->itemInstanceId; // get the instance id to define the subclass

									foreach($characterInventoryData->Response->itemComponents->talentGrids->data->$itemInstanceIdSubclass->nodes as $node)
									{
										if($node->nodeIndex == 11 && $node->isActivated == true) // 11 = one of the 4 nodes that is in the top tree of the subclass
										{
											$subclassTree = "Top Tree";
										}
										elseif($node->nodeIndex == 15 && $node->isActivated == true) // 15 = one of the 4 nodes that is in the bottom tree of the subclass
										{
											$subclassTree = "Bottom Tree";
										}
										elseif($node->nodeIndex == 20 && $node->isActivated == true) // 20 = one of the 4 nodes that is in the button tree of the subclass
										{
											$subclassTree = "Middle Tree";
										}
									}

									$classItemsName = '"' . "items-container" . '"'; //container for all items, is within the player container
									echo "<div class=$classItemsName>";

									$itemHashes = [];
									array_push($itemHashes, $characterInventoryData->Response->equipment->data->items[11]->itemHash); //subclass item
									array_push($itemHashes, $characterInventoryData->Response->equipment->data->items[0]->itemHash); //primary weapon
									array_push($itemHashes, $characterInventoryData->Response->equipment->data->items[1]->itemHash); //secondary weapon
									array_push($itemHashes, $characterInventoryData->Response->equipment->data->items[2]->itemHash); //heavy weapon
									array_push($itemHashes, $characterInventoryData->Response->equipment->data->items[3]->itemHash); //helmet
									array_push($itemHashes, $characterInventoryData->Response->equipment->data->items[4]->itemHash); //gauntlets
									array_push($itemHashes, $characterInventoryData->Response->equipment->data->items[5]->itemHash); //chestpiece
									array_push($itemHashes, $characterInventoryData->Response->equipment->data->items[6]->itemHash); //boots

									$i = 0;
									$items = 0;
									foreach ($itemHashes as $itemHash) //foreach item hash in the list
									{
										$perksClass = '"'. 'perk-container-' . $playerCount . $i . ' ' . 'perk-container' . '"';
										$itemClass = '"' . 'item-container-' . $playerCount . $i . ' ' . 'item-container' . '"';	 //container for induvidual item
										$perkClass = '"' . 'single-perk-container' . '"';
										
										$sql = "SELECT * FROM inventory_item_definition WHERE hash=$itemHash";
										$query  = $db->query($sql);
										$item = $query->fetch(PDO::FETCH_ASSOC);

										$itemTypeDisplayName = $item['item_type_display_name'];
										$itemTypeAndTierDisplayName = $item['item_type_and_tier_display_name'];

										if($itemTypeDisplayName == "Helmet" && $itemTypeAndTierDisplayName != "Exotic Helmet") //check if its a helmet, dont do anything if it's not exotic
										{

										}
										elseif($itemTypeDisplayName == "Gauntlets" && $itemTypeAndTierDisplayName != "Exotic Gauntlets") //check if they are gauntleys, dont do anything if they're not exotic
										{
											
										}
										elseif($itemTypeDisplayName == "Chest Armor" && $itemTypeAndTierDisplayName != "Exotic Chest Armor")  //check if its a chestpiece, dont do anything if it's not exotic
										{
											
										}
										elseif($itemTypeDisplayName == "Leg Armor" && $itemTypeAndTierDisplayName != "Exotic Leg Armor") //check if they are boots, dont do anything if they're not exotic
										{
											
										}
										elseif($itemTypeDisplayName == "Titan Subclass") // checks if its a titan subclass
										{
											echo "<div class=$itemClass>"; //start item container

											$itemName  = $subclassTree . " " . $item['name']; //define the name and add the tree to it
											echo "<h3>$itemName</h3>"; 

											$imageLink = '"' . 'images/' . $item['hash'] . '.png'. '"';
											$imageClass = '"' . "subclass-image-container" . '"'; //container for the subclass, requires a rotated border
											echo "<div class=$imageClass>"; //start image container
											echo "<img src=$imageLink>"; 
											echo "</div>"; //end image container
											echo "<div class=$perksClass>"; //start perk class
											echo "</div>";
											echo "</div>"; //end item container
											$classSource = "Titan.svg"; //defines the image for the class
										}
										elseif($itemTypeDisplayName == "Hunter Subclass") // checks if its a hunter subclass
										{
											echo "<div class=$itemClass>"; //start item container

											$itemName  = $subclassTree . " " . $item['name'];
											echo "<h3>$itemName</h3>";

											$imageLink = '"' . 'images/' . $item['hash'] . '.png'. '"';
											$imageClass = '"' . "subclass-image-container" . '"'; //container for the subclass, requires a rotated border
											echo "<div class=$imageClass>";
											echo "<img src=$imageLink>";
											echo "</div>";
											echo "<div class=$perksClass>"; //start perk class
											echo "</div>";
											echo "</div>";
											$classSource = "Hunter.svg"; //defines the image for the class
										}
										elseif($itemTypeDisplayName == "Warlock Subclass") // checks if its a warlock subclass
										{
											echo "<div class=$itemClass>"; //start item container

											$itemName  = $subclassTree . " " . $item['name'];
											echo "<h3>$itemName</h3>";

											$imageLink = '"' . 'images/' . $item['hash'] . '.png'. '"';
											$imageClass = '"' . "subclass-image-container" . '"'; //container for the subclass, requires a rotated border
											echo "<div class=$imageClass>";
											echo "<img src=$imageLink>";
											echo "</div>";
											echo "<div class=$perksClass>"; //start perk class
											echo "</div>";
											echo "</div>";
											$classSource = "Warlock.svg"; //defines the image for the class
										}
										else
										{
											echo "<div class=$itemClass>"; //start item container

											$itemName  = $item['name'];
											echo "<h3>$itemName</h3>";
											$imageLink = '"' . 'images/' . $item['hash'] . '.png'. '"';
											echo "<img src=$imageLink>";
											echo "<div class=$perksClass>"; //start perk class
											$sql = "SELECT socket_hash FROM sockets_per_item WHERE item_hash=$itemHash";
											$query = $db->query($sql);
											$perks = $query->fetchAll(PDO::FETCH_ASSOC);

											/*
											foreach($item->sockets->socketEntries as $socketEntry)
											{
												$perkHash = $socketEntry->singleInitialItemHash;
												if($perkHash != 0)
												{
													$sql = "SELECT data FROM inventory_item_definition WHERE hash=$perkHash";
													$query  = $db->query($sql);
													$perkString = $query->fetchAll(PDO::FETCH_ASSOC);
													$perk = json_decode($perkString[0]['data']);

													if(property_exists($perk->displayProperties, 'icon'))
													{
														$iconUrlPerk = '"' . 'images/' . $perk->hash . '.png'. '"';
														echo "<div class=$perkClass>";
														echo "<img src=$iconUrlPerk>";
														echo "</div>";
													}
												}
											}*/	
											$items++;
											echo "</div>";
											echo "</div>";
										}
										$i++;
									}
									if($items != 4)
									{
										echo "<div class=$itemClass>";
										echo "<h3></h3>";
										echo "<div class=$imagePlaceholderClass>";
										echo "</div>";
										echo "</div>";
									}
									echo "</div>";

									$playerName = $player->Response->profile->data->userInfo->displayName;
									$modifiedPlayerName = preg_replace('/[^a-z0-9]/i', '',  $playerName);
									$playernameClassName = '"'. "playername-container" . '"';
									echo "<div class=$playernameClassName>";
									$imageSrc = '"' . $classSource . '"'; 
									echo "<h2>$modifiedPlayerName <img src=$imageSrc></h2>";
									echo "</div>";
									echo "</div>";
								}
							}
							$playerCount++;
						}
						echo "</div>";
					echo "</div>";
				}
			}
			else
			{

			}
		?>
	</main>
	<footer>
		<h2>  </h2>
	</footer>
</body>
</html>
