<?php
require "../includes/session.php";
require "../../modele/includes/identifiants.php";
require "../../modele/includes/fonctions.php";

$forum = (int) $_GET['f'];
$data = getForumInfos($forum , $bdd);

$titre = $data['forum_name'] | .' SiteduSavoir.com'; 


require "../includes/debut.php";
include("../includes/constantes.php");
include ("../../modele/includes/debut.php");
$balises=(isset($balises))?$balises:0;
if($balises)
{
    include('../../vue/includes/debut.php');
}
require "../../vue/includes/menu.php";
require "../includes/menu.php";
require "../includes/fonctions.php";





	
	

	if (!verif_auth($data['auth_view']))
	{
		erreur(ERR_AUTH_VIEW);
	}

	$totalDesMessages = $data['forum_topic'] + 1;
	$nombreDeMessagesParPage = 25;
	$nombreDePages = ceil($totalDesMessages / $nombreDeMessagesParPage);
?>

<?php
	echo '<p id="fildariane"><i>Vous êtes ici</i> : <a href="./index.php">Forum</a> --> <a href="./voirforum.php?f='.$forum.'">'.stripslashes(htmlspecialchars($data['forum_name'])).'</a>';

	$page = (isset($_GET['page']))?intval($_GET['page']):1;
	//On affiche les pages 1-2-3, etc.
	echo '<p>Page : ';
	for ($i = 1 ; $i <= $nombreDePages ; $i++)
	{
		if ($i == $page) //On ne met pas de lien sur la page actuelle
		{
			echo $i;
		} 
		else 
		{
			echo '<a href="voirforum.php?f='.$forum.'&amp;page='.$i.'">'.$i.'</a>';
		}
	}
	echo '</p>';

	$premierMessageAafficher = ($page - 1) * $nombreDeMessagesParPage;
	//Le titre du forum
	echo '<h1 class="titre">'.stripslashes(htmlspecialchars($data['forum_name'])).'</h1><br/><br />';
	//Et le bouton pour poster


	if (verif_auth($data['auth_topic']))
	{
		//Et le bouton pour poster
		echo'<a href="./poster.php?action=nouveautopic&amp;f='.$forum.'"><img src="../images/nouveau.gif" alt="Nouveau topic" title="Poster un nouveau topic"></a>';
	}

	$add1='';
	$add2 ='';

	if ($id!=0) //on est connecté
	{
		//Premièrement, sélection des champs
		$add1 = ',tv_id, tv_post_id, tv_poste';

		//Deuxièmement, jointure
		$add2 = 'LEFT JOIN forum_topic_view
		ON forum_topic.topic_id = forum_topic_view.tv_topic_id AND
		forum_topic_view.tv_id = :id';
	}

	$query = $bdd->prepare('SELECT forum_topic.topic_id, topic_titre,
	topic_createur, topic_vu, topic_post, topic_time, topic_last_post,
	Mb.membre_pseudo AS membre_pseudo_createur, post_createur,
	post_time, Ma.membre_pseudo AS membre_pseudo_last_posteur,post_id '.$add1.' FROM forum_topic
	LEFT JOIN membres Mb ON Mb.membre_id = forum_topic.topic_createur
	LEFT JOIN forum_post ON forum_topic.topic_last_post = forum_post.post_id
	LEFT JOIN membres Ma ON Ma.membre_id = forum_post.post_createur
	'.$add2.' WHERE topic_genre = "Annonce" AND forum_topic.forum_id =:forum ORDER BY topic_last_post DESC');

	$query->bindParam(':forum',$forum,PDO::PARAM_INT);

	if($id!=0)
	$query->bindParam(':id',$id,PDO::PARAM_INT);
	$query->execute();
?>

<?php
	//On lance notre tableau seulement s'il y a des requêtes !
	if ($query->rowCount()>0)
	{
?>

<table>
<tr>
<th><img src="../images/annonce.png" alt="Annonce" /></th>
<th class="titre"><strong>Titre</strong></th>
<th class="nombremessages"><strong>Réponses</strong></th>
<th class="nombrevu"><strong>Vus</strong></th>
<th class="auteur"><strong>Auteur</strong></th>
<th class="derniermessage"><strong>Dernier message</strong></th>
</tr>
<?php
	while ($data=$query->fetch())
	{
		
	//Pour chaque topic :
	//Si le topic est une annonce on l'affiche en haut
	//mega echo de bourrain pour tout remplir

		if (!empty($id)) // Si le membre est connecté
		{
			if ($data['tv_id'] == $id) //S'il a lu le topic
			{
				if ($data['tv_poste'] == '0') // S'il n'a pas posté
				{
					if ($data['tv_post_id'] == $data['topic_last_post'])
					//S'il n'y a pas de nouveau message
					{
						$ico_mess = 'message.png';
					}
					else
					{
						$ico_mess = 'messagec_non_lus.png'; //S'il y a un nouveau message
					}
				}
				else // S'il a posté
				{
					if ($data['tv_post_id'] == $data['topic_last_post'])
					//S'il n'y a pas de nouveau message
					{
						 $ico_mess = 'messagep_lu.png';
					}
					else //S'il y a un nouveau message
					{
						$ico_mess = 'messagep_non_lu.png';
					}
				}
			}
			else //S'il n'a pas lu le topic
			{
				$ico_mess = 'message_non_lu.png';
			}
		}
		//S'il n'est pas connecté
		else
		{
			$ico_mess = 'message.png';
		}

		echo'<tr><td><img src="../images/'.$ico_mess.'" alt="Annonce"
		/></td>
		<td id="titre"><strong>Annonce : </strong>
		<strong><a href="./voirtopic.php?t='.$data['topic_id'].'"
		title="Topic commencé à '.$data['topic_time'].'">
		'.stripslashes(htmlspecialchars($data['topic_titre'])).'</a></strong></td>
		<td class="nombremessages">'.$data['topic_post'].'</td>
		<td class="nombrevu">'.$data['topic_vu'].'</td>
		<td><a href="./voirprofil.php?m='.$data['topic_createur'].'
		&amp;action=consulter">
		'.stripslashes(htmlspecialchars($data['membre_pseudo_createur'])).'</a></td>';
		//Selection dernier message
		$nombreDeMessagesParPage = 15;
		$nbr_post = $data['topic_post'] +1;
		$page = ceil($nbr_post / $nombreDeMessagesParPage);
		echo '<td class="derniermessage">Par
		<a href="./voirprofil.php?m='.$data['post_createur'].'
		&amp;action=consulter">
		'.stripslashes(htmlspecialchars($data['membre_pseudo_last_posteur'])).'</a><br/>
		A <a href="./voirtopic.php?t='.$data['topic_id'].'&amp;page='.$page.'#p_'.$data['post_id'].'">'.$data['post_time'].'</a></td></tr>';
	}
?>
</table>
<?php
	}
	$query->CloseCursor();
?>

<?php

	$add1='';
	$add2 ='';

	if ($id!=0) //on est connecté
	{
		//Premièrement, sélection des champs

		$add1 = ',tv_id, tv_post_id, tv_poste';
		//Deuxièmement, jointure
		$add2 = 'LEFT JOIN forum_topic_view
		ON forum_topic.topic_id = forum_topic_view.tv_topic_id AND
		forum_topic_view.tv_id = :id';
	}


	//On prend tout ce qu'on a sur les topics normaux du forum
	$query = $bdd->prepare('SELECT forum_topic.topic_id, topic_titre, topic_createur,topic_vu, topic_post,DATE_FORMAT(topic_time,\'%d/%m/%Y %h:%i:%s\') AS topic_time , topic_last_post,
	Mb.membre_pseudo AS membre_pseudo_createur, post_id, post_createur, post_time,
	Ma.membre_pseudo AS membre_pseudo_last_posteur '.$add1.' FROM forum_topic
	LEFT JOIN membres Mb ON Mb.membre_id = forum_topic.topic_createur
	LEFT JOIN forum_post ON forum_topic.topic_last_post = forum_post.post_id
	LEFT JOIN membres Ma ON Ma.membre_id = forum_post.post_createur
	'.$add2.'
	WHERE topic_genre <> "Annonce" AND forum_topic.forum_id = :forum
	ORDER BY topic_last_post DESC
	LIMIT :premier ,:nombre');
	$query->bindValue(':forum',$forum,PDO::PARAM_INT);

	if($id!=0)
	{
		$query->bindParam(':id',$id,PDO::PARAM_INT);
	}

	$query->bindValue(':premier',(int) $premierMessageAafficher,PDO::PARAM_INT);
	$query->bindValue(':nombre',(int) $nombreDeMessagesParPage,PDO::PARAM_INT);
	$query->execute();

	if ($query->rowCount()>0)
	{

?>
<table>
<tr>
<th><img src="../images/sujet.png" alt="Message" /></th>
<th class="titre"><strong>Titre</strong></th>
<th class="nombremessages"><strong>Réponses</strong></th>
<th class="nombrevu"><strong>Vus</strong></th>
<th class="auteur"><strong>Auteur</strong></th>
<th class="derniermessage"><strong>Dernier message </strong></th>
</tr>

<?php
	//On lance la boucle
	while ($data = $query->fetch())
	{
  	if (!empty($id)) // Si le membre est connecté
		{
  		if ($data['tv_id'] == $id) //S'il a lu le topic
  		{
  			if ($data['tv_poste'] == '0') // S'il n'a pas posté
  			{
  				if ($data['tv_post_id'] == $data['topic_last_post'])
  				//S'il n'y a pas de nouveau message
  				{
  					$ico_mess = 'message.png';
  				}
  				else
  				{
  					$ico_mess = 'messagec_non_lus.png'; //S'il y a un nouveau message
  				}
  			}
  			else // S'il a posté
  			{
  				if ($data['tv_post_id'] == $data['topic_last_post'])
  				//S'il n'y a pas de nouveau message
  				{
  					$ico_mess = 'messagep_lu.png';
  				}
  				else //S'il y a un nouveau message
  				{
  					$ico_mess = 'messagep_non_lu.png';
  				}
  			}
  		}
	    else
	      { //S'il n'a pas lu le topic	{
		    $ico_mess = 'message_non_lu.png';
		  }
    }
    //S'il n'est pas connecté
    else
    {
      $ico_mess = 'message.png';
    }

	  //Ah bah tiens... re vla l'echo de fou
		echo'<tr><td><img src="../images/'.$ico_mess.'" alt="Message"/></td>
		<td class="titre">
		<strong><a href="./voirtopic.php?t='.$data['topic_id'].'" title="Topic commencé à
		'.$data['topic_time'].'">
		'.stripslashes(htmlspecialchars($data['topic_titre'])).'</a></strong></td>
		<td class="nombremessages">'.$data['topic_post'].'</td>
		<td class="nombrevu">'.$data['topic_vu'].'</td>
		<td><a href="./voirprofil.php?m='.$data['topic_createur'].'
		&amp;action=consulter">
		'.stripslashes(htmlspecialchars($data['membre_pseudo_createur'])).'</a></td>';
		//Selection dernier message
		$nombreDeMessagesParPage = 15;
		$nbr_post = $data['topic_post'] +1;
		$page = ceil($nbr_post / $nombreDeMessagesParPage);
		echo '<td class="derniermessage">Par<a href="./voirprofil.php?m='.$data['post_createur'].'
		&amp;action=consulter">
		'.stripslashes(htmlspecialchars($data['membre_pseudo_last_posteur'])).'</a><br
		/>
		A <a href="./voirtopic.php?
		t='.$data['topic_id'].'&amp;page='.$page.'#p_'.$data['post_id'].'">'.$data['post_time'].'</a></td></tr>';
	}
?>

</table>

<?php

  }

  else
  {
    echo'<p>Ce forum ne contient aucun sujet actuellement</p>';
  }

  $query->CloseCursor();
?>

</div>
</body>
</html>