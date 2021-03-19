<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlayerController extends AbstractController
{
    /**
     * @Route("/", name="main")
     */
    public function main(): Response 
    {
        
        return $this->render('index.html.twig', [
            'controller_name' => 'PlayerController' ]);
        //return $this->redirect('/player');
    }

    /**
     * @Route("/player", name="player")
     */
    public function index(): Response
    {
        $url = 'http://www.sofascore.com/team/football/stade-rennais/1658/'; 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        
        preg_match_all("!player/[a-z][^\s]*?/[0-9]*?!",$response,$matcheslnplayers);  //recupère les URLS vers les profils des joueurs

        $playersln = array_unique($matcheslnplayers[0]);
       
        $thetab = array();
        $j = 0;
        foreach ($playersln as $p){
            $playerName = explode('/',$playersln[$j]);
            $playerName = $playerName[1];
            $playerName = ucwords(str_replace('-',' ',$playerName));
            $playerName = str_replace(' ','+',$playerName);
            $urlp = "https://www.google.com/search?q=$playerName&tbm=isch"; //récupère l'URL du joueur visé
            $chp = curl_init();
            curl_setopt($chp, CURLOPT_URL, $urlp);
            curl_setopt($chp, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($chp, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($chp, CURLOPT_RETURNTRANSFER, 1);
            $responsep = curl_exec($chp);
            curl_close($chp);
            $dom = new \DOMDocument();
            @$dom-> loadHTML($responsep);
            $finder = new \DomXPath($dom);
            $i = $finder->query('//img')->item(1)->getAttribute('src');
            $playerName = str_replace('+',' ',$playerName);
            $tab = array($i,$p,$playerName); //fais un tableau avec l'URL du joueur et sa photo
            array_push($thetab,$tab);
            $j++;
        }


        curl_close($ch);

        return $this->render('player/players.html.twig', [
            'controller_name' => 'PlayerController', 'tab' => $thetab
        ]);
    }

    /**
     * @Route("/player/{id}", name="player_show")
     */
    public function show_player($id): Response
    {
        $url = 'http://www.sofascore.com/team/football/stade-rennais/1658/';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);

        curl_close($ch);

        preg_match_all("!player/[a-z][^\s]*/?!",$response,$lna); //recupère les URLS vers les profils des joueurs
        $ln = array_unique($lna[0]);
     
        foreach($ln as $l){
            if(str_contains($l,$id)){
                $lnplayer = $l;
            }
        }

        //supprime le contenu indesirable récupéré
        $lnplayer = explode(">",$lnplayer,2);
        $lnplayer = $lnplayer[0];
        $lnplayer = explode('"',$lnplayer,2);
        $lnplayer = $lnplayer[0];  

        $url = "http://www.sofascore.com/$lnplayer"; //récupère l'URL du joueur visé
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        //correction d'un nom de joueur mal écrit
        if($id=="sehrou-guirassy"){
            $id = "serhou-guirassy";
        }

        $name = ucwords(str_replace('-',' ',$id)); 

        $playerName = str_replace(' ','+',$name);
        $urlp = "https://www.google.com/search?q=$playerName&tbm=isch"; //récupère l'URL du joueur visé
        $chp = curl_init();
        curl_setopt($chp, CURLOPT_URL, $urlp);
        curl_setopt($chp, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chp, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($chp, CURLOPT_RETURNTRANSFER, 1);
        $responsep = curl_exec($chp);
        curl_close($chp);
        $domp = new \DOMDocument();
        @$domp-> loadHTML($responsep);
        $finderp = new \DomXPath($domp);
        $image = $finderp->query('//img')->item(1)->getAttribute('src');
    
        $dom = new \DOMDocument();
        @$dom-> loadHTML($response);

        $finder = new \DomXPath($dom);
        $sousf = $finder->query("//*[contains(@class, 'styles__DetailBoxContent-sc-1ss54tr-12 ExNjU')]"); //charge les statistiques propres au joueur (taille,age..)
        $dateNaissance = $sousf[1]->textContent;

        //bon pied manquant pour certains joueurs
        $surf = $finder->query("//*[contains(@class, 'styles__DetailBoxTitle-sc-1ss54tr-11 gMYPyy')]"); 
        if($surf->item(5)!==null){
            $number = $surf->item(5)->textContent;
            $poste = $surf->item(4)->textContent;
        } else {
            $number = $surf->item(4)->textContent;
            $poste = $surf->item(3)->textContent;
        }
        $taille = $surf->item(2)->textContent;
        $age = $surf->item(1)->textContent;
        $nationalite = $surf->item(0)->textContent;

        if($poste == "F"){
            $poste = "Attaquant";
        } else if($poste == "M"){
            $poste = "Milieu";
        } else if($poste == "D"){
            $poste = "Défenseur";
        } else {
            $poste = "Gardien";
        }

        //liste des joueurs avec lien vers stats
        $url = 'https://fr.wikipedia.org/wiki/Stade_rennais_football_club#Effectif_professionnel_actuel';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        //deconstruction du nom pour faire face aux différents cas : un prénom sans nom, un nom avec particule, un nom simple.
        $nom = explode(' ',$name);
        $prenom = $nom[0];
        if(empty($nom[1])){
            unset($nom);
        } else {
            if(empty($nom[2])){
                $nom = $nom[1];
            } else {
                $nom = $nom[1].'_'.$nom[2];
            }
        }

        $dom = new \DOMDocument();
        @$dom-> loadHTML($response);
    
        $finder = new \DomXPath($dom);

        $contrat = $finder->query("//*[contains(@class, 'toccolours centre')]")->item(0);  //recupère la date d'expiration du contrat
        $rows = $contrat->getElementsByTagName('tr');
        foreach ($rows as $row) {
            $contrat = "inconnu";
            $cells = $row -> getElementsByTagName('td');
            if(isset($cells[3])){
                if(isset($nom)){
                    $nomSansTiret = str_replace('_',' ',$nom);
                    $cells[3]->nodeValue = str_replace('é','e',$cells[3]->nodeValue);
                    if(str_contains($cells[3]->nodeValue,$nomSansTiret)){
                        $contrat = explode("-",$cells[7]->nodeValue);
                        $contrat = $contrat[1];
                        break;
                    }
                } else {
                    if(str_contains($cells[3]->nodeValue,$prenom)){
                        $contrat = explode("-",$cells[7]->nodeValue);
                        $contrat = $contrat[1];
                        break;
                    }
                }
            }
        }

        preg_match_all("!\/wiki\/[A-z][^\s]*!",$response,$lnallnamewiki); //recupère l'URL des joueurs vers leurs pages wikipédia
        $lnallnamewiki = array_unique($lnallnamewiki[0]);
        
        //recupère l'URL souhaitée
        if(isset($nom)){
            foreach($lnallnamewiki as $l){
                if(str_contains($l,$nom) && str_contains($l,$prenom)){
                    $lnstatsplayer = $l;
                }
            }
    
            if(!(isset($lnstatsplayer))){
                foreach($lnallnamewiki as $l){
                    if(str_contains($l,$nom)){
                        $lnstatsplayer = $l;
                    }
                }
            }
        }

        if(!(isset($lnstatsplayer))){
            foreach($lnallnamewiki as $l){
                if(str_contains($l,$prenom)){
                    $lnstatsplayer = $l;
                }
            }
        }

        if(isset($lnstatsplayer)){
            if($lnstatsplayer[-1]=='"'){
                $lnstatsplayer = substr($lnstatsplayer,0,-1);
            }

            $url = "https://fr.wikipedia.org/$lnstatsplayer";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);
            
            $dom = new \DOMDocument();
            @$dom-> loadHTML($response);
    
            $finder = new \DomXPath($dom);
            
            //parcours amateur sur page de Grenier
            if(isset($nom)){
                if($nom=="Grenier"){
                    $statstable = $finder->query("//*[contains(@class, 'wikitable fstats alternance2')]")->item(1); 
                } else {
                    $statstable = $finder->query("//*[contains(@class, 'wikitable fstats alternance2')]")->item(0); 
                }
            } else {
                //recupère les statistiques carrière du joueur
                $statstable = $finder->query("//*[contains(@class, 'wikitable fstats alternance2')]")->item(0); 
            }
        
            if(empty($statstable)){
                //page wikipédia non trouvée pour le joueur
                $saisons = array(array("2020/2021","Stade Rennais FC",0,0,0),array("TOTAL","",0,0,0));
                $error = "Problème de redirection sur Wikipedia pour le lien https://fr.wikipedia.org$lnstatsplayer.
                Pour régler le problème -> créer une redirection vers la bonne page depuis Wikipédia.";
                $total = array(0,0,0);
            } else {
                $th = $statstable->getElementsByTagName('th');
                $assists = false;
                foreach($th as $t){
                    //vérification de la présence des passes décisives
                    if($t->nodeValue == "Pd"){
                        $assists = true;
                        break;
                    }
                }
                $rows = $statstable->getElementsByTagName('tr');
                $saisons = array();
                $j = 0;
                //si passes décisives dispo
                if($assists){
                    foreach ($rows as $row) {
                        $cells = $row -> getElementsByTagName('td');
                        $line = array();
                        $i = 0;
                        if(isset($cells[1]->nodeValue)){
                            //éviter d'enregistrer les sous totaux
                            if(is_numeric($cells[1]->nodeValue) && $j!= count($rows)-1){
                                $j++;
                                continue;
                            } else {
                                $passageTotal = true;
                                foreach ($cells as $cell) {
                                    //éviter d'enregistrer le total entier
                                    if($j != count($rows)-1){
                                        if($i==0 || $i==1 || $i==count($cells)-3 || $i==count($cells)-2 || $i==count($cells)-1){
                                            array_push($line,$cell->nodeValue);
                                        }
                                    } else {
                                        if($passageTotal){
                                            array_push($line,"");
                                            array_push($line,"");
                                        }
                                        $passageTotal = false;
                                        if( $i==count($cells)-3 || $i==count($cells)-2 || $i==count($cells)-1 ){
                                            array_push($line,$cell->nodeValue);
                                        }
                                    }
                                    $i++;
                                }
                                array_push($saisons,$line);
                            }
                        }
                        $j++;
                    }
                } else {
                    foreach ($rows as $row) {
                        $cells = $row -> getElementsByTagName('td');
                        $line = array();
                        $i = 0;
                        if(isset($cells[1]->nodeValue)){
                            //eviter d'enregistrer les sous totaux
                            if(is_numeric($cells[1]->nodeValue) && $j!= count($rows)-1){
                                $j++;
                                continue;
                            } else {
                                foreach ($cells as $cell) {
                                    //eviter d'enregistrer le total entier
                                    if($j != count($rows)-1){
                                        if($i==0 || $i==1 || $i==count($cells)-2 || $i==count($cells)-1 ){
                                            array_push($line,$cell->nodeValue);
                                        }
                                    } else {
                                        array_push($line,"");
                                        if( $i==count($cells)-2 || $i==count($cells)-1 ){
                                            array_push($line,$cell->nodeValue);
                                        }
                                    }
                                    $i++;
                                }
                                array_push($saisons,$line);
                            }
                        }
                        $j++;
                    }
                }

                $total = array(0,0,0);
                for($j = 0; $j<count($saisons);$j++){
                    if(empty($saisons[$j][0])){
                        continue;
                    }

                    if(str_contains($saisons[$j][1],"rennais")){
                        $saisons[$j][1]="Stade Rennais FC";
                    }

                    $l = 1;
                    while(str_contains($saisons[$j][1],"Ligue")){
                        $saisons[$j][1] = $saisons[$j-$l][1];
                        $l++;
                    }

                    //calcul du total sur la carrière des buts, apparitions et passes décisives
                    $total[0] = $total[0] + (int)$saisons[$j][2];
                    $total[1] = $total[1] + (int)$saisons[$j][3];
                    if(isset($saisons[$j][4])){
                        $total[2] = $total[2] + (int)$saisons[$j][4];
                    } else {
                        unset($total[2]);
                    }
                    
                }
                $error = "";
            }
            $lasts = array_pop($saisons);
            $columns = array_column($saisons,0);
            array_multisort($columns, SORT_ASC, $saisons);
            $saisons = array_reverse($saisons);
        } else {
            //page wikipédia inexistante pour le joueur
            $saisons = array(array("2020-2021","Stade Rennais FC",0,0,0));
            $error = "Le joueur n'a pas de page sur Wikipedia donc pas de stats.
                Pour régler le problème -> créer une page pour ce joueur sur Wikipédia avec comme URL : https://fr.wikipedia.org/$prenom"."_"."$nom";
            $total = array(0,0,0);
        }
       
        return $this->render('player/player_view.html.twig', [
            'controller_name' => 'PlayerController', 'id' => $name, 'photo' => $image, 'numero' => $number, 'poste' => $poste,
            'nation' => $nationalite, 'age' => $age, 'dateNaissance' => $dateNaissance, 'taille' => $taille, 'tabstats' => $saisons, 'contrat' => $contrat, 'total' => $total, 'error' => $error
        ]);
    }


}
