<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Player;
use App\Entity\Statistics;

class PlayerController extends AbstractController
{
    /**
     * @Route("/", name="main")
     */
    public function main(): Response 
    {
        
        return $this->render('index.html.twig', [
            'controller_name' => 'PlayerController' ]);
    }

    /**
     * @Route("/player", name="player")
     */
    public function index(): Response
    {
        $url = 'https://fr.wikipedia.org/wiki/Stade_rennais_football_club#Effectif_professionnel_actuel';
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

        $manager = $this->getDoctrine()->getManager();

        $table = $finder->query("//*[contains(@class, 'toccolours centre')]")->item(0);  //recupère la date d'expiration du contrat
        $rows = $table->getElementsByTagName('tr');
        $i = 0;
        foreach ($rows as $row) {
            if($i>1){
                $player = new Player();
                $cells = $row ->getElementsByTagName('td');
                if (!empty($cells[0]->nodeValue)) {
                    $player->setNumber(intval($cells[0]->nodeValue));
                }
                if (!empty($cells[1]->nodeValue)) {
                    $player->setPoste($cells[1]->nodeValue);
                }
                if (!empty($cells[3]->nodeValue)) {
                    $names = explode(" ",$cells[3]->nodeValue);
                    if(count($names) == 3){
                        $firstname = substr($names[1],strlen($names[1])/2);
                        $player->setFirstname($firstname);
                        $lastname = explode(" ",$names[2])[0];
                        $lastname = str_replace("\n","",$lastname);
                        $player->setLastname($lastname);
                    } else {
                        $firstname = substr($names[2],strlen($names[2])/2);
                        $player->setFirstname($firstname);
                        $lastname = $names[3] . ' ' . explode(",",$names[1])[0];
                        $player->setLastname($lastname);
                    }
                   
                    $playerExists = $this->getDoctrine()->getRepository(Player::class)->findOneBy([
                        'firstname' => $firstname, 'lastname' => $lastname
                    ]);
                    if($playerExists!=null){
                        continue;
                    } 

                    $fullname = $player->getFirstname() . '+' . $player->getLastname();
                    $fullname = str_replace(" ","+",$fullname);
                    $urlp = "https://www.google.com/search?q=$fullname+ligue+1&tbm=isch"; //récupère l'URL du joueur visé
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
                    $image = $finder->query('//img')->item(1)->getAttribute('src');
                    $player->setImage($image);
                }

                if (!empty($cells[4]->nodeValue)) {
                    $birthdate = explode("(",$cells[4]->nodeValue)[0];
                    $bdate = \DateTime::createFromFormat('d-m-Y',$birthdate);
                    $bd = new \DateTime($bdate);
                    $player->setBirthdate($bd);
                    $player->setNationality(" ");
                    $manager->persist($player);
                }
                
            }
            $i++;
        }
        $manager->flush();

        $players = $this->getDoctrine()->getRepository(Player::class)->findAll();
        
        return $this->render('player/players.html.twig', [
            'players' => $players
        ]);
    }

    /**
     * @Route("/player/{firstname}/{lastname}", name="player_show")
     */
    public function show_player($firstname,$lastname): Response
    {
        $url = 'https://fr.wikipedia.org/wiki/Stade_rennais_football_club#Effectif_professionnel_actuel';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $manager = $this->getDoctrine()->getManager();

        preg_match_all("!\/wiki\/[A-z][^\s]*!",$response,$linkWiki);
        $linkWiki = array_unique($linkWiki[0]);
        $player = $this->getDoctrine()->getRepository(Player::class)->findOneBy(
            ['firstname' => $firstname, 'lastname' => $lastname]
        );
        
        if($player->getWikilink() == null){
            foreach($linkWiki as $n){
                if(str_contains($n,$player->getFirstname()) && str_contains($n,$player->getLastname())){
                    $player->setWikilink($n);
                } else if(str_contains($n,$player->getLastname())){
                    $player->setWikilink($n);
                }
            }
        }

        if($player->getWikilink() == null){
            if($player->getStatistics()==null){
                $stats = new Statistics();
                $stats->setSeason("2020/2021");
                $stats->setAppearances(0);
                $stats->setGoals(0);
                $stats->setAssists(0);
                $stats->setClub("Stade Rennais FC");
                $stats->setPlayer($player);
                $player->setStatistics($stats);
                $manager->persist($stats);
            }
        } else {
            if($player->getStatistics()->count() == 0){
                $wikilink = $player->getWikilink();
                $wikilink = substr($wikilink,0,-1);
                $url = "https://fr.wikipedia.org/$wikilink";
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
    
                if($player->getLastname()=="Grenier"){
                    $statstable = $finder->query("//*[contains(@class, 'wikitable fstats alternance2')]")->item(1); 
                } else {
                    $statstable = $finder->query("//*[contains(@class, 'wikitable fstats alternance2')]")->item(0); 
                }
    
                if(empty($statstable)){
                    //page wikipédia non trouvée pour le joueur
                    $stats = new Statistics();
                    $stats->setSeason("2020-2021");
                    $stats->setAppearances(0);
                    $stats->setGoals(0);
                    $stats->setAssists(0);
                    $stats->setClub("Stade Rennais FC");
                    $stats->setPlayer($player);
                    $player->addStatistic($stats);
                    $manager->persist($stats);
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
                    $j = 0;
                    //si passes décisives dispo
                    if($assists){
                        foreach ($rows as $row) {
                            $stats = new Statistics();
                            $cells = $row -> getElementsByTagName('td');
                            $line = array();
                            $i = 0;
                            if(isset($cells[1]->nodeValue)){
                                //éviter d'enregistrer les sous totaux
                                if(is_numeric($cells[1]->nodeValue) && $j!= count($rows)-1){
                                    $j++;
                                    continue;
                                } else {
                                    foreach ($cells as $cell) {
                                        
                                        //éviter d'enregistrer le total entier
                                        if($j != count($rows)-1){
                                            if($i==0){
                                                $stats->setSeason($cell->nodeValue);
                                            } else if($i==1){
                                                if($cell->nodeValue == " Stade rennais FC  
                                                "){
                                                    $stats->setClub("Stade Rennais FC");
                                                } else {
                                                    $stats->setClub($cell->nodeValue);
                                                }
                                            } else if($i==count($cells)-3){
                                                $stats->setAppearances($cell->nodeValue);
                                            } else if($i==count($cells)-2){
                                                $stats->setGoals($cell->nodeValue);
                                            } else if($i==count($cells)-1){
                                                $stats->setAssists(intval($cell->nodeValue));
                                            }
                                        }
                                        $i++;
                                    }
                                }
                            }
                            $stats->setPlayer($player);
                            if($stats->getSeason()!=null){
                                $manager->persist($stats);
                            }
                            $j++;
                        }
                    } else {
                        foreach ($rows as $row) {
                            $stats = new Statistics();
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
                                            if($i==0){
                                                $stats->setSeason($cell->nodeValue);
                                            } else if($i==1){
                                                if($cell->nodeValue == " Stade rennais FC  
                                                "){
                                                    $stats->setClub("Stade Rennais FC");
                                                } else {
                                                    $stats->setClub($cell->nodeValue);
                                                }
                                            } else if($i==count($cells)-2){
                                                $stats->setAppearances($cell->nodeValue);
                                            } else if($i==count($cells)-1){
                                                $stats->setGoals(intval($cell->nodeValue));
                                               
                                            }
                                        }
                                        $i++;
                                        
                                    }
                                }
                            }
                            $j++;
                            $stats->setAssists(0);
                            $stats->setPlayer($player);
                            if($stats->getSeason()!=null){
                                $manager->persist($stats);
                            }
                        }
                    }
                }
                $manager->persist($player);
            }       
        }
        $manager->flush();
        
        return $this->render('player/player_view.html.twig', [
            'player' => $player
        ]);
    }


}
