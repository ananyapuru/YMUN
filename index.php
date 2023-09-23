<?php 
	error_reporting(E_ALL);
    session_start(); 
    require 'lang.php';
    require 'includes/incl.functions.php';
    include 'vendor/autoload.php';
    $storagelocation = '.';
    $users       = new \Filebase\Database(array('dir'   => $storagelocation.'/database/users'));
	$committee = [];
    if (!$users->has('admin')) {
        $users->get('admin')->save([
            'username' => 'admin',
            'password' => password_hash("00000000", PASSWORD_BCRYPT)            
            ]
        );
    }
    if (isset($_SESSION['logged']) && $users->has($_SESSION['logged'])) {
        $storagelocation = "database/users_db/".$_SESSION['logged'];
		$delegations    = new \Filebase\Database(array('dir'   => $storagelocation.'/delegations'));
		$topics         = new \Filebase\Database(array('dir'   => $storagelocation.'/topics'));
		$unmod          = new \Filebase\Database(array('dir'   => $storagelocation.'/unmod'));
		$votes          = new \Filebase\Database(array('dir'   => $storagelocation.'/votes'));
		$settings       = new \Filebase\Database(array('dir'   => $storagelocation.'/settings'));
		$committee = $settings->get('committee');
		
    }
    getLibs();
    $dev            = false;
    $tpl            = "template/";
    $loader         = new \Twig\Loader\FilesystemLoader(__DIR__ . "/$tpl");
    $twig           = new \Twig\Environment($loader, ['cache' => false]);
    $voteFunction   = new \Twig\TwigFunction('getVotes', function($id) {
        global $votes;
        $stat = $votes->query()->where('results.voted', 'IN', $id)->results();
        return $stat;
    });
    $getDate = new \Twig\TwigFunction('getDate', function($stat) {
        global $votes;
       return $votes->get($stat['id'])->updatedAt();
    });
    $format = new \Twig\TwigFunction('time', function($ss) {
        $s = $ss%60;
        $m = floor(($ss%3600)/60);
        $h = floor(($ss%86400)/3600);
        $d = floor(($ss%2592000)/86400);
        $M = floor($ss/2592000);
    
        $s = $s < 10 ? '0' . $s : $s;
        $m = $m < 10 ? '0' . $m : $m;
        $h = $h < 10 ? '0' . $h : $h;
        $d = $d < 10 ? '0' . $d : $d;
        $M = $M < 10 ? '0' . $M : $M;
    
        return $ss>0?"<i title='$M:$d:$h:$m:$s'>$M:$d:$h:$m:$s</i>":" 0 ";
    }) ;
    $twig->addFunction($format);
    $twig->addFunction($voteFunction);  
    $twig->addFunction($getDate);  
    $load       = [
        'fs'    => $files,
        'tpl'   => $tpl,
        'hash'  => "?v=".(($dev)? time() : "1.0"),
        'Lang'  => $Lang,
        'SESSION' => $_SESSION,
		'committee' => $committee
    ];
 
    if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['log']) && $_GET['log'] == 'out') {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['last_visited'] = 'Dashboard';
        header('Location: index.php');
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {   
        if (isset($_SESSION['logged']))
            $load['committee'] = $settings->get('committee'); 
        $l = (isset($_SESSION['last_visited']))?$_SESSION['last_visited']:'';
        if (in_array($l, ["General", "Special", "ModCaucus", "UnmodCaucus"]))
            $_SESSION['vlast_visited'] = $l;
        $page = $_POST['p'];
        $action = (isset($_POST['action']))? true: false;
        if (!$action) {
            switch ($page):
                case "Statistics":
                    $Page = "Statistics.html";
                    if (isset($_SESSION['logged']))
                        $load['delegations'] = $delegations->query()->orderBy('stats.total', 'DESC')->results();
                    $load['title'] = $Lang['titles']['statistics'];
                    $_SESSION['last_visited'] = $page;

                break;
                case "Voting":
                    if (isset($_SESSION['logged'])) {
                        $present = $delegations->query()->where("rollcall.present", "=", "true")->orderBy('name')->results();
                        $load['delegations'] = $present;
                        $load['number'] = count($present);
                    }
                    $Page = "Voting.html";
                    $load['title'] = $Lang['titles']['voting'];
                    $_SESSION['last_visited'] = $page;

                break;
                case "UnmodCaucus":
                    $Page = "Unmoderated-Caucus.html";
                    $load['title'] = $Lang['titles']['unmodcaucus'];
                    $_SESSION['last_visited'] = $page;

                break;
                case "ModCaucus":
                    if (isset($_SESSION['logged']))
                        $load['delegations'] = $delegations->query()->where("rollcall.present", "=", "true")->orderBy('name')->results();
                    $Page = "Moderated-Caucus.html";
                    $load['title'] = $Lang['titles']['modcaucus'];
                    $_SESSION['last_visited'] = $page;

                break;
                case "General":
                    if (isset($_SESSION['logged']))
                        $load['delegations'] = $delegations->query()->where("rollcall.present", "=", "true")->orderBy('name')->results();
                    $Page = "General-Speakers.html";
                    $load['title'] = $Lang['titles']['general'];
                    $_SESSION['last_visited'] = $page;

                break;
                case "Special":
                    if (isset($_SESSION['logged']))
                        $load['delegations'] = $delegations->query()->where("rollcall.present", "=", "true")->orderBy('name')->results();
                    $Page = "Special-Speakers.html";
                    $load['title'] = $Lang['titles']['special'];
                    $_SESSION['last_visited'] = $page;

                break;
                case "Presence": 
                    if (isset($_SESSION['logged']))
                        $load['delegations'] = $delegations->query()->orderBy('name', 'ASC')->results();
                    $Page = "Presence.html";
                    $load['title'] = $Lang['titles']['presence'];
                    $_SESSION['last_visited'] = $page;
                break;
                case "Dashboard":
                    $Page = "Dashboard.html";
                    if (isset($_SESSION['logged']))
                        $load['delegations'] = $delegations->query()->orderBy('name', 'ASC')->results();
                    $load['title'] = $Lang['titles']['dashboard'];
                    $_SESSION['last_visited'] = $page;
                break;
                case "D-Delegations":
                    $Page = "Dashboard-delegations.html";
                    $load['delegations'] = $delegations->query()->orderBy('name', 'ASC')->results();
                    $load['title'] = $Lang['titles']['dashboard'];
                    $_SESSION['last_visited'] = "Dashboard";
                break;
                case "D-Committee":
                    $Page = "Dashboard-committee.html";
                    $load['committee'] = $settings->get('committee');
                    $load['title'] = $Lang['titles']['dashboard'];
                    $_SESSION['last_visited'] = "Dashboard";
                break;
                case "D-Credentials":
                    $Page = "Dashboard-credentials.html";
                    $load['admin'] = $settings->get($_SESSION['logged']);
                    $load['title'] = $Lang['titles']['dashboard'];
                    $_SESSION['last_visited'] = "Dashboard";
                break;
                default:
                $Page = "Home.html";
            endswitch;
            if (!isset($_SESSION['logged']))
                $Page = 'Dashboard.html';
            echo $twig->load("pages/".$Page)->render($load);
        } else {
            switch($page):
                case 'logged':
                    $logged = isset($_SESSION['logged'])?$_SESSION['logged']:null;
                    if ($logged)
                    echo 'SUCCESS';
                    else echo 'ERROR';
                break;
                case 'save':
                    $result = ['status' => 'error', 'errorCode' => 'Unknown'];
                    $name = htmlentities(trim($_POST['name']));
                    $stime = $_POST['s'];
                    $ttime = $_POST['t'];
                    if (strlen($name)>0)
                        $committee->name = $name;

                    if (isset($stime) && $stime>0)
                        $committee->default_speaking_time = $stime;

                    if (isset($ttime) && $ttime >= $committee->default_speaking_time )
                        $committee->default_total_time = $ttime;
                    if ($committee->save())
                        $result = [
                            'status' => 'SUCCESS',
                            'name' => $committee->name, 
                            'stime' => $committee->default_speaking_time,
                            'ttime' => $committee->default_total_time
                        ];
                    else
                        $result = [
                            'status' => 'ERROR',
                            'errorCode' => 'Error: Could not save the new data!'
                        ];
                    echo json_encode($result);
                break;
                case 'save-cred':
                    $password = $_POST['password'];
                    $newpassword = $_POST['newpassword']; 
                    $errors = [];
                    $user = $users->get($_SESSION['logged']);
                    if (!password_verify($password, $user->password))
                        $errors[] = 'wrong_pw';
                    if (strlen($newpassword) < 8)
                        $errors[] = 'wrong_npw';
                        
                    $result = ['status' => 'error', 'errorCode' => $errors];
                    if (count($errors) == 0 )
                    if ($user->save([
                        'username' => $username,
                        'password' => password_hash($newpassword, PASSWORD_BCRYPT)
                    ])) 
                        $result = [
                            'status' => 'SUCCESS'
                        ];
                    else
                        $result = [
                            'status' => 'ERROR',
                            'errorCode' => 'Error: Could not save the new data!'
                        ];
                    echo json_encode($result);
                break;
                case 'save-user':
                    $password = $_POST['password'];
                    $username = $_POST['username']; 
                    $errors = [];
                    if (strlen($password) < 8)
                        $errors[] = 'Password must have atleast 8 characters';
                    if (strlen($username) < 5)
                        $errors[] = 'Username must have atleast 5 characters';
                    if ($users->has($username)) {
                        $errors[] = 'Username in use';
                    } 
                        
                    $result = ['status' => 'error', 'errorCode' => $errors];
                    if (count($errors) == 0 ) {
                        $user = $users->get($username); 
                        if ($user->save([
                            'username' => $username,
                            'password' => password_hash($password, PASSWORD_BCRYPT)
                        ])) 
                            $result = [
                                'status' => 'SUCCESS'
                            ];
                        else
                            $result = [
                                'status' => 'ERROR',
                                'errorCode' => 'Error: Could not save the new data!'
                            ];
                    }
                    echo json_encode($result);
                break;
                case 'title':
                    $p = $_POST['f'];
                    if (isset($p) && $p !== "" && $p !== NULL)
                        echo $Lang['titles'][$p];
                break;
                case 'roll-call':
                    $id     = $_POST['id'];
                    $p      = $_POST['present'];
                    $v      = $_POST['voting'];
                    $c      = $delegations->get($id);
                    $c->rollcall['present'] = $p;
                    $c->rollcall['voting'] = $v;
                    $c->save();
                break;
                case 'flags':
                    $limit = $_POST['limit'];
                    $Cpage = $_POST['from'];
                    $from = $Cpage*$limit;
                    $flags = getFlags($limit,$from);
                    $pages = ceil($flags[1]/$limit);


                    foreach ($flags[0] as $flag)
                    echo '<div class="s-flag" data-name="'.$flag['name'].'" data-flag="'.$flag['flag'].'"><img src="'.$flag['flag'].'" alt="'.$flag['name'].'" /></div>';

                    echo '<div class="pagination">';
                    if ($Cpage >= 1)
                        echo '<div class="page-prev" onclick="loadFlags('.$limit.', '.($Cpage-1).')"><i class="fa fa-arrow-left"></i></div>';
                        else echo '<div></div>';
                        echo '<div class="page-item">'.($Cpage+1)."/$pages</div>";
                    if ($Cpage + 1 < $pages)
                        echo '<div class="page-next" onclick="loadFlags('.$limit.', '.($Cpage+1).')"><i class="fa fa-arrow-right"></i></div>';
                    else echo '<div></div>';
                    echo '</div>';
                break;
                case 'Login':
                    $username = htmlentities(trim(strtolower($_POST['username'])));
                    $password = $_POST['password'];
                    if ($users->has($username)) {
                        $userdata = $users->get($username);
                        if (password_verify($password, $userdata->password)) {
                            $_SESSION['logged'] = $username;
                            $_SESSION['admin'] = ($username=='admin');
                            $_SESSION['loggedTime'] = time();

                            echo 'SUCCESS';
                        } else {
                            echo 'WRONG_PW';
                        }
                    } else {
                        echo 'NO_RECORD';
                    }
                break;

                case 'add-delegation':
                    $name = htmlentities(trim($_POST['name']));
                    $flag = $_POST['flag'];
                    if ($delegations->query()->where('name', '=', $name)->count() > 0) {
                        echo 'DELEGATION EXISTS';
                    } else {
                        $id = uniqid();
                        if ($delegations->get($id)->save([
                            'name' => $name,
                            'flag' => $flag,
                            'id' => $id
                        ]))
                        echo 'SUCCESS';
                        else echo 'ERROR';
                    }
                break;

                case 'delete':
                    $id = $_POST['id'];
                    if ($delegations->get($id)->delete()) {
                        echo 'SUCCESS';
                    } else {
                        echo 'Error: Deletion incomplete!';
                    }
                break;

                case 'delegation':
                    $result = ['status' => 'error'];
                    $id = $_POST['id'];
                    $delegation = $delegations->get($id);
                    if (isset($delegation->name) && $delegation->name !== "")
                        $result = array_merge(['status' => 'SUCCESS'], $delegations->toArray($delegation));
                    echo json_encode($result);
                break;
                case 'search-delegation':
                    $v = htmlentities(trim($_POST['v']));
                    $ds = $delegations->query()->where('name', 'LIKE', $v)->andWhere('rollcall.present', '=', 'true')->orderBy('name')->results();
                    echo json_encode($ds);
                break;
                case 'update-stat':
                    $id = $_POST['id'];
                    $type = $_POST['t'];
                    $tid = @$_POST['tid'];
                    $Stat = $delegations->get($id);
                    $stat = $Stat->stats;
                    $general = $stat['general'];
                    $special = $stat['special'];
                    @$moderated = $stat['moderated'];
                    @$modtime = $moderated[$tid];
                    @$modTtime = $moderated['time'];
                    $topic = "";
                    $total = $stat['total'];

                    if ($type == 'g') $general++;
                    if ($type == 's') $special++;
                    if ($type == 'm') {
                        $modtime++;
                        $moderated[$tid] = $modtime;
                        $modTtime++;
                        $moderated["time"] = $modTtime;
                    }
                    if ($type!=='u') {
                        $total++;
                        $Stat->stats['general'] = $general;
                        $Stat->stats['special'] = $special;
                        $Stat->stats['moderated'] = $moderated;
                        $Stat->stats['total'] = $total;
                        $Stat->save();
                    } else {
                        $u = $unmod->get($tid);
                        $time = $u->time;
                        $time++;
                        $u->save(['time' => $time]);
                    }
                break;
                case 'new-topic':
                    $t = $_POST['t'];
                    if (trim(htmlentities(($t))) !== '') {
                    $s = $topics->query()->where('topic', '=', $t)->resultDocuments();
                    $c = count($s);
                    $topic = @$s[0];
                    if (!@isset($topic->topic) || @$topic->topic == '' || $c == 0 ) {
                        $topic = $topics->get(uniqid());
                        $topic->save([
                            'topic' => $t
                        ]);
                    }

                    $session = $settings->get('session');
                    $session->save([
                        "topic" => $t,
                        "topic_id" => $topic->getId()
                    ]);
                    echo $topic->topic;
                    } else {
                        echo 'Please, enter a value!';
                    }
                break;
                case 'get-session':
                    $session = $settings->get('session');
                    echo json_encode($settings->toArray($session));
                break;
                case "get-vote-session":
                    $voting = $settings->get('vote');
                    echo json_encode($settings->toArray($voting));
                break;
                case "save-vote-session":
                    $data = json_decode($_POST['data']);
                    $t = $_POST['t'];
                    $a = $_POST['a'];
                    $id = uniqid();
                    if ($votes->get($id)->save([
                        'id' => $id,
                        'session_name' => $a,
                        'vote_type' => $t,
                        'results' => $data
                    ])) {
                        echo 'SUCCESS';
                    } else {
                        echo 'ERROR';
                    }
                break;
                case "vote":
                    $id = $_POST['id'];
                    $t = $_POST['t'];
                    $vote = $votes->get($id);
                break;
                case 'upload':
                    $file = $_FILES['file']['name'];
                    $destination_path = "database/uploads/".$_SESSION['logged']."/";
                    if (!file_exists($destination_path)) mkdir($destination_path, 0777, true);
                    $ext = explode('.', $file); 
                    $ext = $ext[count($ext)-1];   

                    $uid = uniqid();
                    $target_path = $destination_path . $uid .".$ext";
                    while (file_exists($target_path )) {
                        $uid = uniqid();
                        $target_path = $destination_path . $uid .".$ext";
                    }             
                    if(@move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
                       echo $target_path;
                    } else {
                        echo 'ERROR';
                    } 
                    sleep(1);
                break;
                case 'reset-stats':
                    $id = $_POST['id'];
                    $delegation = $delegations->get($id);
                    $delegation->stats = null;
                    if ($delegation->save()) {
                        echo 'SUCCESS';
                    } else {
                        echo 'ERROR';
                    }
                break;
                case 'reset-all':
                    $all = $_POST['all'];
                    $errors = 0;
                    if ( $all == 'yes' )
                        if (!$votes->query()->delete())
                            $errors++;
                    

                    $ds = $delegations->findAll();
                    foreach ($ds as $delegation) {
                        $delegation->stats = null;
                        if (!$delegation->save())
                            $errors++;
                    }
                    
                    if ($errors>0) {
                        echo 'SUCCESS';
                    } else {
                        echo 'ERROR';
                    }
                break;
                default:
            endswitch;
        }

    } else {
    $template = $twig->load('index.html');
    $template = $template->render($load);
    echo $template;
    }
?>
