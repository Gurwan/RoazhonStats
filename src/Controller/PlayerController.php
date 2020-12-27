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
        return $this->redirect('/player');
    }

    /**
     * @Route("/player", name="player")
     */
    public function index(): Response
    {
        //http://rougememoire.com/player/serhou-guirassy/
        $url = 'http://www.sofascore.com/team/football/stade-rennais/1658/'; //'http://www.sofascore.com/team/football/stade-rennais/1658/'
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        //"!https://www.sofascore.com/images/player/image_139228.png!"

        $response = curl_exec($ch);
        
        preg_match_all("!player/[a-z][^\s]*?/[0-9]*?!",$response,$matcheslnplayers);
        preg_match_all("!https://www.sofascore.com/images/player/image_[0-9]*?.png!",$response,$matchesimg);

        $images = array_unique($matchesimg[0]);
        $playersln = array_unique($matcheslnplayers[0]);
       

        $thetab = array();
        $j = 0;
        foreach ($images as $i){
            $tab = array($i,$playersln[$j]);
            array_push($thetab,$tab);
            $j++;
        }

        curl_close($ch);

        

        return $this->render('player/index.html.twig', [
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

        preg_match_all("!player/[a-z][^\s]*/?!",$response,$lna);
        $ln = array_unique($lna[0]);
     
        foreach($ln as $l){
            if(str_contains($l,$id)){
                $lnplayer = $l;
            }
        }

        $lnplayer = explode(">",$lnplayer,2);
        $lnplayer = $lnplayer[0];
        $lnplayer = explode('"',$lnplayer,2);
        $lnplayer = $lnplayer[0];

        $url = "http://www.sofascore.com/$lnplayer";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        if($id=="sehrou-guirassy"){
            $id = "serhou-guirassy";
        }

        $name = ucwords(str_replace('-',' ',$id));
       

        preg_match_all("!https://www.sofascore.com/images/player/image_[0-9]*?.png!",$response,$matchesimg);
        $images = array_unique($matchesimg[0]);
    
        $dom = new \DOMDocument();
        @$dom-> loadHTML($response);

        $finder = new \DomXPath($dom);
        $sousf = $finder->query("//*[contains(@class, 'styles__DetailBoxContent-sc-1ss54tr-12 ExNjU')]"); 
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

        preg_match_all("!\/wiki\/[A-z][^\s]*!",$response,$lnallnamewiki);
        $lnallnamewiki = array_unique($lnallnamewiki[0]);
        $lnallnamewiki = str_replace('%C3%A9','e',$lnallnamewiki);
        $lnallnamewiki = str_replace('%27B','b',$lnallnamewiki);
        $lnallnamewiki = str_replace('%27','',$lnallnamewiki);
        
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
            echo $lnstatsplayer;
        } else {
            $saisons = array(array("20/21","Stade Rennais",0,0,0),array("TOTAL","TOTAL",0,0,0));
        }

        //ne marche pas -> plusieurs personnes avec même prénom !!

        
 




        /*
        foreach($lnstatsplayers as $l){
            if(str_contains($l,$nom)){
                $lnstatsplayer = $l;
            }
        }
       
        if(isset($lnstatsplayer)){
            $lnstatsplayer = explode('>',$lnstatsplayer);
            $lnstatsplayer = $lnstatsplayer[0];
            $lnstatsplayer = substr($lnstatsplayer,1,-1);
            
            //stats du joueur
            $url = "https://www.statbunker.com$lnstatsplayer&comps_type=-1&dates=-1";
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
        
            $statstable = $finder->query("//*[contains(@class, 'table')]")->item(0); 
            $rows = $statstable->getElementsByTagName('tr');

            $i = 0;
            $saisons = array();
            foreach ($rows as $row) {
                $cells = $row -> getElementsByTagName('td');
                $line = array();
                foreach ($cells as $cell) {
                    if($i%13==0){
                        if(empty($cell->nodeValue)){
                            $cell->nodeValue = "TOTAL";
                        } else {
                            $explodeseason = explode(' ',$cell->nodeValue);
                            $cell->nodeValue = end($explodeseason);
                        }
                        array_push($line,$cell->nodeValue);
                    } else if($i%13==1){
                        if(empty($cell->nodeValue)){
                            $cell->nodeValue = "TOTAL";
                        } 
                        array_push($line,$cell->nodeValue);
                    } else if($i%13==2 || $i%13==4) {
                        if($cell->nodeValue=="-"){
                            $cell->nodeValue = 0;
                        }
                        array_push($line,$cell->nodeValue);
                    } else if($i%13==10 || $i%13==11){
                        if($cell->nodeValue=="-"){
                            $cell->nodeValue = 0;
                        }
                        array_push($line,$cell->nodeValue);
                    } 
                    $i++; 
                }
                array_push($saisons,$line);
            }

            array_shift($saisons);
            print_r($saisons);
        
            for($j = 0; $j<count($saisons);$j++){
                
                if(empty($saisons[$j][0])){
                    continue;
                }

                if($saisons[$j][1]=="Stade Rennes " || $saisons[$j][1]==" Stade Rennes " || $saisons[$j][1]=="Stade Rennes" || $saisons[$j][1]==" Stade Rennes"){
                    $saisons[$j][1]="Stade Rennais";
                } else {
                    print $saisons[$j][1];
                }

                if($j!=count($saisons)-1){
                    if($saisons[$j][0]==$saisons[$j+1][0]){
                        $saisons[$j][2] = $saisons[$j][2]+$saisons[$j+1][2];
                        $saisons[$j][3] = $saisons[$j][3]+$saisons[$j+1][3];
                        $saisons[$j][4] = $saisons[$j][4]+$saisons[$j+1][4];
                        $saisons[$j][5] = $saisons[$j][5]+$saisons[$j+1][5];
                        unset($saisons[$j+1]);
                    } 
                }
               

                $saisons[$j][2]=$saisons[$j][2]+$saisons[$j][3];
                unset($saisons[$j][3]);
            }
        } else {
            $saisons = array(array("20/21","Stade Rennais",0,0,0),array("TOTAL","TOTAL",0,0,0));
        }
        */

        //print_r($saisons);
       
        $saisons = array(array("20/21","Stade Rennais",0,0,0),array("TOTAL","TOTAL",0,0,0));
        return $this->render('player/player_view.html.twig', [
            'controller_name' => 'PlayerController', 'id' => $name, 'photo' => $images[0], 'numero' => $number, 'poste' => $poste,
            'nation' => $nationalite, 'age' => $age, 'dateNaissance' => $dateNaissance, 'taille' => $taille, 'tabstats' => $saisons
        ]);
    }


}
