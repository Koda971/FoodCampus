<?php

$errors = false;

$nameError = "";
$surnameError = "";
$emailError = "";
$passwordError = "";
$confirmPasswordError = "";
$selectAccountError = "";
$addressError = "";
$crossNumberError = "";
$pivaError = "";
$cityError = "";
$supplierNameError = "";
$shippingError = "";
$shippingLimitError = "";
$fileError = "";
$urlError = "";

$GLOBALS["newFileName"] = "";

$slqError = "";
$queryErrors = array();

require_once "../../database.php";
require_once "../../utilities/create_session.php";
require_once "../../utilities/direct_login.php";
require_once "../../utilities/file_uploader.php";

// Redirect to home page
function redirect($conn, $page) {
	header("Location: $page");
	mysqli_close($conn);
	exit();
}

// If user is already logged in, redirect
if (isUserLogged($conn)) {
	redirect($conn, "../../home/home.php");
}

// Starts a session
function start_session($conn, $isSupplier, $email, $password) {
	// Creo le variabili di sessione
	if ($isSupplier) {
		$query = "SELECT IDFornitore FROM fornitore WHERE email = ?";
	} else {
		$query = "SELECT IDCliente FROM cliente WHERE email = ?";
	}

	if ($stmt = $conn->prepare($query)) {

		$stmt->bind_param("s", $email);

		if ($stmt->execute()) {
			$stmt->store_result();
			$stmt->bind_result($user_id);
			$stmt->fetch();
			// Create session and redirect
			create_Session($user_id , $email, $password, $_POST["account_selection"]);
			redirect($conn, "subscription_success.php");
		} else {
			array_push($queryErrors, "Errore durante l'invio dei dati");
		}
	} else {
		array_push($queryErrors, $conn->error);
	}
}

// Subscripts the user
function do_Subscription($conn, $query, &$queryErrors, $isSupplier) {

	$name = $_POST['name'];
	$surname = $_POST['surname'];
	$email = $_POST['email'];

	if (isset($GLOBALS["newFileName"]) && !empty($GLOBALS["newFileName"])) {
		$image = $GLOBALS["newFileName"];
	} else {
		$image = NULL;
	}
	// Recupero la password criptata dal form di inserimento.
	if (!isset($_POST["p"]) || empty($_POST["p"])) {
		if (isset($_POST["pswd"]) && !empty($_POST["pswd"])) {
			$password = $_POST["pswd"];
		}
	} else {
		$password = $_POST["p"];
	}

	// Crea una chiave casuale
	$random_salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
	// Crea una password usando la chiave appena creata.
	$password = hash('sha512', $password.$random_salt);
	$blocked = 0;

	if ($insert_stmt = $conn->prepare($query)) {

		if ($isSupplier) {
			$name = $_POST['nomefornitore'];
			$address = $_POST['indirizzo'];
			$crossNumber = $_POST['ncivico'];
			$piva = $_POST['piva'];
			$city = $_POST['citta'];
			$supplierName = $_POST['nomefornitore'];
			$shipping = $_POST['shippingcost'];
			$shippingLimit = $_POST['shippinglimit'];

			if (isset($_POST['sitoweb']) && !empty($_POST['sitoweb'])) {
				$web_site = $_POST['sitoweb'];
			} else {
				$web_site = NULL;
			}

			$enabled = 0;

			$insert_stmt->bind_param('ssssddissssssi', $name, $city, $address, $crossNumber, $shipping, $shippingLimit, $enabled, $email, $web_site, $piva, $image, $password, $random_salt, $blocked);
		} else {
			$insert_stmt->bind_param('ssssssi', $name, $surname, $email, $image, $password, $random_salt, $blocked);
		}

	   // Esegui la query ottenuta.
	   if (!$insert_stmt->execute()) {
		   array_push($queryErrors, $insert_stmt->error);
	   } else {
		   // Subscription successfull
		   start_session($conn, $isSupplier, $email, $password);
	   }
	} else {
	   array_push($queryErrors, $conn->error);
   }
}

// Prepares queries for subscription
function subscript($conn, &$queryErrors) {

	if ($_POST["account_selection"] === "Fornitore") {
		$query = "INSERT INTO fornitore (nome, citta, indirizzo_via, indirizzo_numero_civico, costi_spedizione, soglia_spedizione_gratuita, abilitato, email, sito_web, partita_iva, immagine, password, salt, bloccato) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		do_Subscription($conn, $query, $queryErrors, true);

	} else {
		$query = "INSERT INTO cliente (nome, cognome, email, immagine, password, salt, bloccato) VALUES (?, ?, ?, ?, ?, ?, ?)";
		do_Subscription($conn, $query, $queryErrors, false);
	}

}

if ($_SERVER['REQUEST_METHOD'] == "POST") {

	if ($_POST["account_selection"] !== "Fornitore" && (!isset($_POST["name"]) || empty($_POST["name"]))) {
		$nameError = "Inserire un nome";
		$errors = true;
		die($_POST["account_selection"]);
	}

	if ($_POST["account_selection"] !== "Fornitore" && (!isset($_POST["surname"]) || empty($_POST["surname"]))) {
		$surnameError = "Inserire un cognome";
		$errors = true;
	}

	if (!isset($_POST["email"]) || !filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
		$emailError = "Inserire un indirizzo email valido";
		$errors = true;
	}

	if (!isset($_POST["p"]) || empty($_POST["p"])) {
		if (!isset($_POST["pswd"]) || empty($_POST["pswd"])) {
			$errors = true;
			$passwordError = "Inserire una password";
		}
	}

	if (!isset($_POST["c-p"]) || empty($_POST["c-p"])) {
		if (!isset($_POST["confirm-pwd"]) || empty($_POST["confirm-pwd"])) {
			$confirmPasswordError = "Reinserisci qui la password";
			$errors = true;
		} else if (!isset($_POST["pswd"]) || !isset($_POST["confirm-pwd"]) || $_POST["pswd"] != $_POST["confirm-pwd"]) {
			$confirmPasswordError = "Le due password non corrispondono";
			$errors = true;
		}
	} else if (!isset($_POST["p"]) || !isset($_POST["c-p"]) || $_POST["p"] != $_POST["c-p"]) {
		$confirmPasswordError = "Le due password non corrispondono";
		$errors = true;
	}

	if (!isset($_POST["account_selection"]) || empty($_POST["account_selection"]) || ($_POST["account_selection"] !== "Cliente" && $_POST["account_selection"] !== "Fornitore")) {
		$selectAccountError = "Scegliere il tipo di account tra i due presenti";
		$errors = true;
	}

	if (isset($_FILES["filename"]["name"]) && !empty($_FILES["filename"]["name"])) {

		if (isset($_POST["account_selection"]) && !empty($_POST["account_selection"])) {

			if ($_POST["account_selection"] === "Cliente") {
				$filePath = "../../../res/clients/";
			} else {
				$filePath = "../../../res/suppliers/";
			}

			$fileName = basename($_FILES["filename"]["name"]);
			$tempArrayName = explode(".", $fileName);
			$GLOBALS["newFileName"] = $tempArrayName[0].uniqid(mt_rand(1, mt_getrandmax()), false).".".$tempArrayName[1];

			if (!uploadFile($filePath, $GLOBALS["newFileName"], "filename", $fileError)) {
				$errors = true;
			}
		}
	}

	if ($_POST["account_selection"] === "Fornitore") {
		if (!isset($_POST["indirizzo"]) || empty($_POST["indirizzo"])) {
			$addressError = "Inserire un indirizzo";
			$errors = true;
		}

		if (!isset($_POST["ncivico"]) || empty($_POST["ncivico"])) {
			$crossNumberError = "Inserire un numero civico";
			$errors = true;
		}

		if (!isset($_POST["piva"]) || empty($_POST["piva"])) {
			$pivaError = "Inserire una partita IVA";
			$errors = true;
		}

		if (!isset($_POST["citta"]) || empty($_POST["citta"])) {
			$cityError = "Inserire una citt&agrave;";
			$errors = true;
		}

		if (!isset($_POST["nomefornitore"]) || empty($_POST["nomefornitore"])) {
			$supplierNameError = "Inserire il vostro nome fornitore";
			$errors = true;
		}

		if (!isset($_POST["shippingcost"]) || is_null($_POST["shippingcost"]) || $_POST["shippingcost"] < 0 || $_POST["shippingcost"] > 10 || !is_numeric($_POST["shippingcost"])) {
			$shippingError = "Inserire un costo di spedizione compreso tra 0 e 10";
			$errors = true;
		}

		if (!isset($_POST["shippinglimit"]) || is_null($_POST["shippinglimit"]) || $_POST["shippinglimit"] < 0 || $_POST["shippinglimit"] > 10 || !is_numeric($_POST["shippinglimit"])) {
			$shippingLimitError = "Inserire un limite di spedizione gratuita compreso tra 0 e 10";
			$errors = true;
		}

		if (isset($_POST['sitoweb']) && !is_null($_POST["sitoweb"]) && !empty($_POST['sitoweb'])) {
			if (!filter_var($_POST['sitoweb'], FILTER_VALIDATE_URL)) {
				$urlError = "Inserire un URL valido";
				$errors = true;
			}
		}

	}

	function checkUserAlreadyExists($conn, $query, &$queryErrors, &$slqError, &$emailError) {

		if ($stmt = $conn->prepare($query)) {

			$stmt->bind_param('s', $_POST["email"]);

			if (!$stmt->execute()) {
				array_push($queryErrors, "Errore durante l'invio dei dati");
				$slqError = "Errore durante l'invio dei dati";
				return false;
			} else {
				$stmt->store_result();

				if($stmt->num_rows > 0) {
					$emailError = "Un utente è già registrato con questo indirizzo email";
					$slqError = "Un utente è già registrato con questo indirizzo email";
					return true;
				}
			}
		}  else {
			array_push($queryErrors, $conn->error);
		   return false;
	   }
	   return false;

	}

	if (!$errors) {
		$query = "SELECT IDCliente FROM cliente WHERE email = ?";

		if (!checkUserAlreadyExists($conn, $query, $queryErrors, $slqError, $emailError) && strlen($slqError) === 0) {

			$query = "SELECT IDFornitore FROM fornitore WHERE email = ?";

			if (!checkUserAlreadyExists($conn, $query, $queryErrors, $slqError, $emailError) && strlen($slqError) === 0) {
				// Proceed with subscription
				subscript($conn, $queryErrors);
			}
		}

	}
}

?>

<!DOCTYPE html>
<html lang="it-IT">
<head>
	<title>Iscrizione</title>
	<metacharset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
	<!-- jQuery library -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<!-- Popper JS -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
	<!-- Latest compiled JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
	<!--Font awesome-->
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" integrity="sha384-gfdkjb5BdAXd+lj+gudLWI+BXq4IuLW5IT+brZEZsLFm++aCMlF1V92rMkPaX4PP" crossorigin="anonymous">
	<script src="../../../jquery/jquery-3.2.1.min.js"> </script>

	<script src="../js/subscription_account_selection.js"> </script>
	<script src="../js/subscription_input_checker.js"> </script>
	<script src="../../../js/utilities/sha512.js"></script>
	<script src="../../../js/utilities/form_password_encoder.js"></script>

	<link rel="stylesheet" type="text/css" title="stylesheet" href="../../navbar/navbar.css">
	<link rel="stylesheet" type="text/css" title="stylesheet" href="../css/subscription.css">
	<link rel="stylesheet" type="text/css" title="stylesheet" href="../../footer/footer.css">
</head>

<body>
	<?php require_once '../../navbar/navbar.php';?>
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-xl-6 jumbotron" id="loginform">
				<h1 id="first_title">Crea un Account</h1>
				<form id="subscriptionMainForm" action="subscription.php" method="post" enctype="multipart/form-data">
					<div class="form-input-group" id="ClientBasic">
						<h2 class="form-title">Dati personali</h2>
						<div class="form-group">
							<label for="nome">Nome:</label>
							<input type="text" class="form-control" id="nome" required placeholder="Inserisci il nome" name="name" value="<?php echo isset($_POST['name']) ? $_POST['name'] : '' ?>">
							<?php
								if(strlen($nameError) !== 0) {
									echo("<div class='alert alert-danger errorElement'>$nameError</div>");
								}
							?>
						</div>
						<div class="form-group">
							<label for="cognome">Cognome:</label>
							<input type="text" class="form-control" id="cognome" required placeholder="Inserisci il cognome" name="surname" value="<?php echo isset($_POST['surname']) ? $_POST['surname'] : '' ?>">
							<?php
								if(strlen($surnameError) !== 0) {
									echo("<div class='alert alert-danger errorElement'>$surnameError</div>");
								}
							?>
						</div>
					</div>
					<div class="form-input-group">
						<h2 class="form-title">Email & Password</h2>
						<div class="form-group">
							<label for="email">Indirizzo Email:</label>
							<input type="email" class="form-control" id="email" required placeholder="Inserisci email" name="email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : '' ?>">
							<?php
								if(strlen($emailError) !== 0) {
									echo("<div class='alert alert-danger errorElement'>$emailError</div>");
								}
							?>
						</div>
						<div class="form-group">
							<label for="pwd">Password:</label>
							<input type="password" class="form-control" id="pwd" required placeholder="Inserisci password" name="pswd">
							<?php
								if(strlen($passwordError) !== 0) {
									echo("<div class='alert alert-danger errorElement'>$passwordError</div>");
								}
							?>
						</div>
						<div class="form-group">
							<label for="confirm-pwd">Conferma Password:</label>
							<input type="password" class="form-control" id="confirm-pwd" required placeholder="Conferma password" name="confirm-pwd">
							<?php
								if(strlen($confirmPasswordError) !== 0) {
									echo("<div class='alert alert-danger errorElement'>$confirmPasswordError</div>");
								}
							?>
						</div>
						<div class="form-group">
							<label for="myFile">Immagine del profilo (facoltativo):</label>
							<input type="file" id="myFile" name="filename" class="border" accept="image/*">
							<?php
								if(strlen($fileError) !== 0) {
									echo("<div class='alert alert-warning errorElement'>$fileError</div>");
								}
							?>
						</div>
					</div>
					<div class="form-input-group">
						<h2 class="form-title">Tipo di Account</h2>
						<div class="form-group">
							<label for="sel">Scegli il tipo di account che vuoi creare:</label>
							<select class="form-control" id="sel" name="account_selection">
						        <option>Cliente</option>
						        <option>Fornitore</option>
							</select>
							<?php
								if (isset($_POST['account_selection']) && $_POST['account_selection'] === "Fornitore") {
							?>
								<script type="text/javascript">
									document.getElementById('sel').value= "Fornitore";
								</script>
							<?php
								}
							?>
							<?php
								if(strlen($selectAccountError) !== 0) {
									echo("<div class='alert alert-danger errorElement'>$selectAccountError</div>");
								}
							?>
						</div>
					</div>
					<div class="form-input-group" id="form-fornitore">
						<h2 class="form-title">Dati Fornitore</h2>
							<div class="form-group">
								<label for="indirizzo">Indirizzo:</label>
								<input type="text" class="form-control" id="indirizzo" placeholder="Inserisci il tuo indirizzo" name="indirizzo" value="<?php echo isset($_POST['indirizzo']) ? $_POST['indirizzo'] : '' ?>">
								<?php
									if(isset($_POST["account_selection"]) && $_POST["account_selection"] === "Fornitore" && strlen($addressError) !== 0) {
										echo("<div class='alert alert-danger errorElement'>$addressError</div>");
									}
								?>
							</div>
							<div class="form-group">
								<label for="ncivico">Numero Civico:</label>
								<input type="text" class="form-control" id="ncivico" placeholder="Inserisci il numero civico" name="ncivico" value="<?php echo isset($_POST['ncivico']) ? $_POST['ncivico'] : '' ?>">
								<?php
									if(isset($_POST["account_selection"]) && $_POST["account_selection"] === "Fornitore" && strlen($crossNumberError) !== 0) {
										echo("<div class='alert alert-danger errorElement'>$crossNumberError</div>");
									}
								?>
							</div>
							<div class="form-group">
								<label for="piva">Partita IVA:</label>
								<input type="text" class="form-control" id="piva" placeholder="Inserisci la Partita IVA" name="piva" value="<?php echo isset($_POST['piva']) ? $_POST['piva'] : '' ?>">
								<?php
									if(isset($_POST["account_selection"]) && $_POST["account_selection"] === "Fornitore" && strlen($pivaError) !== 0) {
										echo("<div class='alert alert-danger errorElement'>$pivaError</div>");
									}
								?>
							</div>
							<div class="form-group">
								<label for="citta">Citt&agrave;:</label>
								<input type="text" class="form-control" id="citta" placeholder="Inserisci la tua citt&agrave;" name="citta" value="<?php echo isset($_POST['citta']) ? $_POST['citta'] : '' ?>">
								<?php
									if(isset($_POST["account_selection"]) && $_POST["account_selection"] === "Fornitore" && strlen($cityError) !== 0) {
										echo("<div class='alert alert-danger errorElement'>$cityError</div>");
									}
								?>
							</div>
							<div class="form-group">
								<label for="nomefornitore">Nome Fornitore:</label>
								<input type="text" class="form-control" id="nomefornitore" placeholder="Inserisci il nome della tua attivit&agrave;" name="nomefornitore" value="<?php echo isset($_POST['nomefornitore']) ? $_POST['nomefornitore'] : '' ?>">
								<?php
									if(isset($_POST["account_selection"]) && $_POST["account_selection"] === "Fornitore" && strlen($supplierNameError) !== 0) {
										echo("<div class='alert alert-danger errorElement'>$supplierNameError</div>");
									}
								?>
							</div>
							<div class="form-group">
								<label for="costo-spedizione">Costo spedizione:</label>
								<div class="input-group mb-3">
							      <div class="input-group-prepend">
							        <span class="input-group-text">€</span>
							      </div>
							      <input type="number" max="10.00" min="0" step=".01" data-number-to-fixed="2" class="form-control spedition" id="costo-spedizione" name="shippingcost" value="<?php echo isset($_POST['shippingcost']) ? $_POST['shippingcost'] : '0.00' ?>">
							    </div>
								<?php
								  if(isset($_POST["account_selection"]) && $_POST["account_selection"] === "Fornitore" && strlen($shippingError) !== 0) {
									  echo("<div class='alert alert-danger errorElement'>$shippingError</div>");
								  }
							  ?>
							</div>
							<div class="form-group">
								<label for="soglia-spedizione">Soglia spedizione gratuita:</label>
								<div class="input-group mb-3">
							      <div class="input-group-prepend">
							        <span class="input-group-text">€</span>
							      </div>
							      <input type="number" max="10.00" min="0" step=".01" data-number-to-fixed="2" class="form-control spedition" id="soglia-spedizione" name="shippinglimit" value="<?php echo isset($_POST['shippinglimit']) ? $_POST['shippinglimit'] : '0.00' ?>">
							    </div>
								<?php
								  if(isset($_POST["account_selection"]) && $_POST["account_selection"] === "Fornitore" && strlen($shippingLimitError) !== 0) {
									  echo("<div class='alert alert-danger errorElement'>$shippingLimitError</div>");
								  }
							  ?>
							</div>
							<div class="form-group">
								<label for="sitoweb">Sito Web (facoltativo):</label>
								<input type="url" class="form-control" id="sitoweb" placeholder="Inserisci la URL del tuo sito Web" name="sitoweb" value="<?php echo isset($_POST['sitoweb']) ? $_POST['sitoweb'] : '' ?>">
								<?php
								  if(isset($_POST["account_selection"]) && $_POST["account_selection"] === "Fornitore" && strlen($urlError) !== 0) {
									  echo("<div class='alert alert-danger errorElement'>$urlError</div>");
								  }
							  ?>
							</div>
						</div>

					<div class="d-flex justify-content-center">
						<button type="submit" class="btn btn-primary btn-lg" id="submitbtn">Iscriviti</button>
					</div>
					<div class="">
						<p id="privacy">
							Iscrivendoti dichiari di accettare i nostri <a href="#">Termini d'uso</a>
							<br/>e la <a href="#">Privacy Policy</a>.
						</p>
					</div>
					<noscript>
						<div class='alert alert-warning errorElement'>
							<strong>ATTENZIONE:</strong> Questa pagina potrebbe non funzionare correttamente senza JavaScript.
							Per favore, riabilita JavaScript nel tuo Browser e ricarica la pagina.
						</div>
					</noscript>
					<?php
						if(count($queryErrors) > 0) {
							foreach ($queryErrors as &$value) {
							    echo("<div class='alert alert-danger text-center errorElement'>$value</div>");
							}
						}
					?>
				</form>
			</div>
		</div>
	</div>
	<?php
		require_once "../../cookie/cookie.php";
		require_once "../../footer/footer.html";
	?>
</body>
</html>
