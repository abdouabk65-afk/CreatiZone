<?php
session_start();
//etablir la connexion avec la bd 
require_once 'config/bd.php';
//inclure la classe user
require_once 'classes/User.php';

$activeForm = isset($_GET['form']) ? $_GET['form'] : 'login'; //pour savoir sur quel form on est en train de travailler

// si on travail dans login
// traitement de connexion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login']) ){
        $user = new User();
        $user->email = $_POST['email'];
        $mp=$_POST['motdepasse'];
        //verification de l'email
        if($user->emailExists()) {
            $stmt = $user->getByEmail();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            //verification du mot de passe
            if(password_verify($mp, $row['motdepasse'])) {
                $_SESSION['nom'] = $row['nom'];
                $_SESSION['prenom'] = $row['prenom'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['user_id'] = $row['id'];

                header("Location: profil.php");
                exit();
            }else {
                $error = "mot de passe incorrect.";
            }
        }else {
            $error = "aucun compte trouvé avec cet email";
        }
}
//si on travail dans le form register
//traitement d'inscription
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
        $user = new User();
        //attribuer les valeurs des champs remplis par l'utilsateur dans les proprites de la classe user
        $user->nom=$_POST['nom'];
        $user->prenom=$_POST['prenom'];
        $user->email=$_POST['email'];
        $user->motdepasse=$_POST['motdepasse'];

        if ($user->usernameExists()) {
            $error = "Ce nom d'utilisateur existe déjà.";
        } elseif ($user->emailExists()) {
            $error = "Cet email est déjà utilisé.";
        } elseif (strlen($user->motdepasse) < 6) {
            $error = "Le mot de passe doit contenir au moins 6 caractères.";
        } else {
            if($user->create()){
                $activeForm = 'login'; // Basculer vers le formulaire de login pour se connecter
            }
            else{
                $error = "une erreur a survenue lors de l'inscription";
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Sign Up</title>
    <link rel="stylesheet" href="login_signup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!--login form-->
        <div class="form-box login" <?php echo $activeForm == 'login' ? 'block' : 'none'; ?>>
            <form method="POST" action="">
                <h1>Login</h1>
                 <div class="input-box">
                    <input type="text" placeholder="nom" name="nom" required>
                    <i class="fa-solid fa-user"></i>
                </div>
                 <div class="input-box">
                    <input type="text" placeholder="prenom" name="prenom" required>
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="input-box">
                    <input type="text" placeholder="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" placeholder="mot de passe" name="motdepasse" required>
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div class="forgot-link">
                    <a href="#">Forgot password?</a>
                </div>
                <button type="submit" class="btn">Login</button>
                <p>or login with social platforms</p>
                <div class="social-icons">
                    <a href="#"><i class="fa-brands fa-google"></i></a>
                    <a href="#"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#"><i class="fa-brands fa-github"></i></a>
                    <a href="#"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
            </form> 
        </div>

        <!--register form-->
        <div class="form-box register" <?php echo $activeForm == 'register' ? 'block' : 'none'; ?> >
            <form method="POST" action="">
                <h1>Registration</h1>
                <div class="input-box">
                    <input type="text" placeholder="nom" name="nom" required value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="input-box">
                    <input type="text" placeholder="prenom" name="prenom" required value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="input-box">
                    <input type="email" placeholder="nom.prenom@gmail.com" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" placeholder="mot de passe" name="motdepasse" required>
                    <i class="fa-solid fa-lock"></i>
                </div>

                <button type="submit" class="btn">Register</button>
                <p>or register with social platforms</p>
                <div class="social-icons">
                    <a href="#"><i class="fa-brands fa-google"></i></a>
                    <a href="#"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#"><i class="fa-brands fa-github"></i></a>
                    <a href="#"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
            </form> 
        </div>
        <!--toggle box-->
        <div class="toggle-box">
            <!--toggle box left-->
                <div class="toggle-pannel toggle-left">
                    <h1>Hello, Welcome!</h1>
                    <p>Don't have an account?</p>
                    <button class="btn register-btn" <?php echo $activeForm == 'register' ? 'active' : ''; ?>>Register</button>
                </div>
            <!--toggle box right-->
                <div class="toggle-pannel toggle-right">
                    <h1>Welcome Back!</h1>
                    <p>Already have an account?</p>
                    <button class="btn login-btn" <?php echo $activeForm == 'login' ? 'active' : ''; ?> >Login</button>
                </div>
        </div>
    </div>
<script src="login_signup.js"></script>
</body>
</html>