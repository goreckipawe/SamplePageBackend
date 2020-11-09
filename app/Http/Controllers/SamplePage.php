<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

//starałem sie zrobić projekt jak najszybciej ponieważ nie miałem zbyt wiele czasu dlatego wszystkie metody do obróbki danych znajdują się w tym pliku 
//ztego powodu również a także z uwagi na specyfikę projektu nie zastosowałem w nim zabespieczeń takich jak rejestracja, polityka korsów itd

//nie zauważyłem by w panistwa zadaniach było coś o bazie danych do tego projektu dlatego operuję na plikach
//z tego powodu też nie robiłem modeli do danych ani metod do generowania danych testowych
//pragne również dodać ponieważ to pytanie często pada 
//wole stosować zwykłe zapytania do bazy danych niż metody laravela do tego przeznaczone z prostego powodu 
//uważam że jeśli ktoś zna język sql i umie go używać (a ja potrafię :>) to jest to poprostu dużo szybsze i czytelniejsze przynajmniej dla mnie 
//natomiast jeśli zapytania dotyczą wrażliwych danych lub zależy nam bardzo na bezpieczenistwię stosuję poprostu procedury sql do których przesyłam dane
//co daje całkowita kontrole nad zapytaniami

class SamplePage extends Controller
{
    public function agenda(){ //metoda od pobraniea danych z podanego przez panistwa pliku gdzie jednocześnie sprawdzam poprawność danych
        try{
            $content = json_decode(file_get_contents(storage_path('app/agenda.json')), true); //odczytanie danych z pliku

            $paths = $content["paths"];
            $breaks = $content["breaks"];
            $error_on_path = false;

            if($content != null){
                foreach($paths as $key_p => $path){
                    $length = count($path["startTimes"]);
                    foreach($path["startTimes"] as $key => $start_time){
                        $time = Carbon::parse($start_time);//wyznaczam czas konicowy dla każdej godziny przez dodanie do niej czasu trawania 
                        $endTime = $time->addMinutes($path["durations"][$key]);
                        $path_number = filter_var($path["path"], FILTER_SANITIZE_NUMBER_INT);
                        $content["paths"][$key_p]["endTimes"][$key] = $endTime->format('H:i');

                        foreach($breaks as $key_b => $break){//sprawdzam czy godziny zgadzają się po uwzględnieniu przerw
                            if (strpos($break["title"], $path_number) == true) {
                                $break_time = Carbon::parse($break["startTime"]);

                                if ($break_time == $endTime) {
                                    if ($key != $length - 1) {
                                        $key_temp = $key + 1;
                                        $time_next = Carbon::parse($path["startTimes"][$key_temp]);
                                        $break_time_end = $break_time->addMinutes($break["duration"]);

                                        if ($break_time_end > $time_next) {
                                            $error_on_path = true;
                                        } 
                                    }
                                }
                            }
                        }

                        if ($key != $length - 1) {
                            $time_next = Carbon::parse($path["startTimes"][$key + 1]);

                            if ($time_next < $endTime) {
                                $error_on_path = true;
                            }
                        }
                    }

                    //sprawdzenie czy wystąpił jakiś błąd na danej ścieżce jeśli tak usuwam ją ze zbioru w ten sposób pozbywam się błędnych danych
                    //uznałem że biorąc pod uwagę treść zadania będzie to najlepsza walidacja danych 
                    if ($error_on_path == true) {
                        unset($content["paths"][$key_p]);
                    }
                }
            }

            //dane zwracam w postać tablicy gdzie wartość data zawsze przechowuje dane 
            //małe wyjaśnienie komunikat o błędzie może mieć oczywiście dwie postacie tą którą podajemy urzytkownikowi i tą która widzimy my
            //dlatego w zwracanej tablicy podaję dwie wartości o błędach error w którym można przesłać nazwę tłómaczenia które wyświetli się urzytkownikowi 
            //oraz error_message gdzie znajduje się błąd wynikający z kodu

            //jak widac kod staram się zawsze umieszczać w bloku try catch co pozwala w razie błędu zwrucić puste tablice z wartościami i uniknąć wysypania się aplikacji

            $score = array("success" => 1, "error" => "", "data" => $content);
            
            return json_encode($score);

        } catch (Exception $e) {
            $score = array("success" => 0, "error" => "error", "data" => array(), "error_message" => $e->getMessage());
            
            return json_encode($score);
        }
    }

    public function agendaUserAdd(Request $request){//metoda do zapisu danych wybranych przez urzytkownika
        try{
            $tab = $request->all(); //działam na tablicach bo jest to poprostu dla mnie bardziej wygodne

            $content = json_decode(file_get_contents(storage_path('app/agenda.json')), true);

            $paths = $content["paths"];
            $breaks = $content["breaks"];
            $length = count($tab);
            $error_in_the_plan = false;

            //z racji że w treści zadania nie było nic o bazie danych do projektu operuję na plikach jak wspomniałem wcześniej 
            //dlatego więc przesyłam indekcy danych które wybiera użytkownik
            //i wybieram za ich pomocą dane z pliku
            //następnie sprawdzam czy nie kolidują one ze sobą
            //no i zwracam informacje czy dane się zgadzają czy nie za pomocą flagi

            foreach($tab as $key => $lecture){
                $path_id = $lecture["path_id"];
                $topic_id = $lecture["topic_id"];

                if ($key != $length - 1) {
                    $time = Carbon::parse($paths[$path_id]["startTimes"][$topic_id]);
                    $endTime = $time->addMinutes($paths[$path_id]["durations"][$topic_id]);
                    $path_id_temp = $tab[$key + 1]["path_id"];
                    $topic_id_temp = $tab[$key + 1]["topic_id"];
                    $time_next = Carbon::parse($paths[$path_id_temp]["startTimes"][$topic_id_temp]);
                    $path_number = filter_var($paths[$path_id]["path"], FILTER_SANITIZE_NUMBER_INT);
                    $path_number_next = filter_var($paths[$path_id_temp]["path"], FILTER_SANITIZE_NUMBER_INT);

                    if (($path_number == 1 || $path_number == 3) && ($path_number_next == 2 || $path_number_next == 4)) {
                        $endTime = $endTime->addMinutes(10);
                    }

                    if ($time_next < $endTime) {
                        $error_in_the_plan = true;
                    }
                }
            }

            if ($error_in_the_plan) {
                $score = array("success" => 0, "error" => "lectures_overlap");

                return json_encode($score);
            } else {
                $score = array("success" => 1, "error" => "");

                //jesli wybrane dane się zgadzają zapisuje je w pliku agenda_plan.json jako że w projekcie nie ma bazy 
                //nie chciałem też zaśmiecać projektu dlatego dane przy każdym zapiśe nadpisują dane w podanym pliku
                Storage::disk('local')->put('agenda_plan.json', json_encode($tab));
                
                return json_encode($score);
            }
        } catch (Exception $e) {
            $score = array("success" => 2, "error" => "error", "error_message" => $e->getMessage());
            
            return json_encode($score);
        }
    }

    public function agendaUserGet(){//metoda do pobrania danych zapisanych przez urzytkownika
        try{
            $content = json_decode(file_get_contents(storage_path('app/agenda.json')), true);
            $content_user = json_decode(file_get_contents(storage_path('app/agenda_plan.json')), true);
            $user_lectures = array();
            
            if ($content_user != null && $content != null) {
                foreach($content_user as $key => $lecture){
                    $user_lectures[$key]["path"] = $content["paths"][$lecture["path_id"]]["path"];
                    $user_lectures[$key]["topics"] = $content["paths"][$lecture["path_id"]]["topics"][$lecture["topic_id"]];
                    $user_lectures[$key]["durations"] = $content["paths"][$lecture["path_id"]]["durations"][$lecture["topic_id"]];
                    $user_lectures[$key]["startTimes"]= $content["paths"][$lecture["path_id"]]["startTimes"][$lecture["topic_id"]];
    
                    $time = Carbon::parse($user_lectures[$key]["startTimes"]);
                    $endTime = $time->addMinutes($user_lectures[$key]["durations"]);
                    $user_lectures[$key]["endTimes"] = $endTime->format('H:i');
                    $path_number = filter_var($user_lectures[$key]["path"], FILTER_SANITIZE_NUMBER_INT);
                }
            }

            $score = array("success" => 1, "data" => $user_lectures, "error" => "");

            return json_encode($score);

        } catch (Exception $e) {
            $score = array("success" => 0, "data" => array(), "error" => "error", "error_message" => $e->getMessage());
            
            return json_encode($score);
        }
    }
}
