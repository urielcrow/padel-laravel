<?php

function setImage($dir,$subdir,$image) : string{
    $url="";
    if($image){
        try{
            $newdir = public_path("$dir/$subdir");
            if(!\File::isDirectory($newdir))
                \File::makeDirectory( $newdir, 0755, true);

            $date = date("ymdHis");
            $id = md5(uniqid(microtime()));
        
            $ext = new \SplFileInfo($image->getClientOriginalName());
            $ext = strtolower($ext->getExtension());

            $image_name = "$dir-$date$id$subdir.$ext";
            $image_path_name = $subdir."/".$image_name;
            \Storage::disk($dir)->put($image_path_name , \File::get($image));
            $url = asset("$dir/$image_path_name");
        }
        catch(Exception $e){
            $url="";
        }
        finally{
            return $url;
        }
    }
    return $url;
}

function deleteImage($dir,$subdir) : void{
    $path = "$dir/$subdir";
    $files = glob("$path/*");

    foreach ($files as $file){
        $name = basename($file);
        unlink(storage_path("app/$dir/$subdir/$name"));
    }

}

function getImage($dir,$subdir,$image) : string{
    $path = "$dir/$subdir";
    $files = glob("$path/$image*");
    $url="";

    foreach ($files as $file){
        $name = basename($file);
        $url = asset("$dir/$subdir/$name");
    }

    if(empty($url)){
        $url = asset("assets/no-image.jpg");
    }

    return $url;
}

function passwdCrow($length){

    $alphaUpper = Array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
    $alphaLower = Array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
    $number = Array('1','2','3','4','5','6','7','8','9','0');
    $specials = Array('@','$','!','%','*','?','_','.');

    function generarPasswd($sizePasswd,$group){
        $groupSize = count($group);
        if( $groupSize < 1)
            return "";
        
        $passwd = '';
        $sizeArray = 0;
        $arrays = [];
        $check=[];

        foreach( $group as $index => $element){
            array_push($arrays,$element);
            $check[$index]=false;
        }
        
        for($i=0;$i<$sizePasswd-$groupSize;$i++){
            $posicion = rand(0, $groupSize - 1);//elejimos un arreglo aleatorio
            $sizeArray = count($arrays[$posicion]);//obtenemos el tamaño de ese arreglo aleatorio
            $passwd .= $arrays[$posicion][rand(0,$sizeArray - 1)];//tomamos aleatoriamente un elemento de ese arreglo aleatorio
            $check[$posicion]=true;
        }

        for($i=0;$i<$groupSize;$i++){//Garantizamos que almenos tenga 1 elemento de cada tipo
            $sizeArray = count($arrays[$i]);
            $passwd .= $arrays[$i][rand(0,$sizeArray - 1)];
        }

        return $passwd;
    }

    return generarPasswd($length,[$alphaUpper,$alphaLower,$number,$specials]);
}



