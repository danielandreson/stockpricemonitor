<?php
	
	//Retreive Data
	include "conn.php";
	
  	$sql=mysqli_query($conn, "SELECT distinct StockSymbol FROM stock_condition");

	if (mysqli_num_rows($sql) > 0) 
	{
	    while($row = mysqli_fetch_assoc($sql)) 
	    {	        
	    	echo "Stock Symbol = " . $row["StockSymbol"];

	        $html = file_get_contents( "https://www.indopremier.com/module/saham/stockInfoNews.php?code=" . $row["StockSymbol"] );

			$dom = new DOMDocument();
			libxml_use_internal_errors( 1 );      // <-- add this line to avoid DOM errors
			$dom->loadHTML( $html );

			$elements = $dom->getElementsByTagName('span')[1];
			$Price =str_replace(",","",$elements->nodeValue);
			echo " | Price = " . $Price . "<br/>";

			$sqlInsert=mysqli_query($conn, "INSERT INTO stock_lastprice(StockSymbol,StockPrice,CreateDate,CreateBy) VALUES('". $row["StockSymbol"] ."','". $Price ."',NOW(),'Admin')");
	    }
	} 

	//Anlysis Logic
	$sql=mysqli_query($conn, "SELECT StockSymbol,Operators,StockValue,CreateBy,LastPrice FROM stock_condition");

	if (mysqli_num_rows($sql) > 0) 
	{
		while($row = mysqli_fetch_assoc($sql)) 
	    {
	    	$sqlLastPrice=mysqli_query($conn, "SELECT StockSymbol,StockPrice,CreateDate,CreateBy FROM stock_lastprice WHERE StockSymbol='". $row["StockSymbol"] ."' ORDER BY CreateDate DESC Limit 1 ");

	    	while($rowLastPrice = mysqli_fetch_assoc($sqlLastPrice)) 
	    	{
	    		if($row["LastPrice"]!=$rowLastPrice["StockPrice"])
	    		{
		    		if($rowLastPrice["StockPrice"]>($row["LastPrice"]+($row["LastPrice"]*0.01)))
		    		{
		    			$sqlInsert=mysqli_query($conn, "UPDATE stock_condition SET LastPrice='". $rowLastPrice["StockPrice"] ."', Status='UP', isReport=0 WHERE StockSymbol='". $row["StockSymbol"] ."'");
		    		}
		    		else if($rowLastPrice["StockPrice"]<($row["LastPrice"]+($row["LastPrice"]*0.01)))
		    		{
						$sqlInsert=mysqli_query($conn, "UPDATE stock_condition SET LastPrice='". $rowLastPrice["StockPrice"] ."', Status='DOWN', isReport=0 WHERE StockSymbol='". $row["StockSymbol"] ."'");
		    		}
		    	}
	    	}	
	    }
	}

	//Send Alert
   
	$sql=mysqli_query($conn, "SELECT StockSymbol,Operators,StockValue,CreateBy,LastPrice,Status FROM stock_condition WHERE isReport=0");

	while($row = mysqli_fetch_assoc($sql)) 
	{
	    echo 'Send Alret';
	    
		if($row["Status"]=="UP")
		{
			$Message=$row["StockSymbol"] . " " . $row["LastPrice"];

			$apiToken = "980881805:AAHUXLNf4okY8WazqEAJjbXpYiLZ2B4aPRk";

			$data = [
			    'chat_id' => '@StockPrice_UP',
			    'text' => $Message,
			];
			$response = file_get_contents("https://api.telegram.org/bot$apiToken/sendMessage?" . http_build_query($data) );

			$sqlInsert=mysqli_query($conn, "UPDATE stock_condition SET isReport=1 WHERE StockSymbol='". $row["StockSymbol"] ."'");
			
			echo 'send success';
		}
		else if($row["Status"]=="DOWN")
		{
			$Message=$row["StockSymbol"] . " " . $row["LastPrice"];

			$apiToken = "-";

			$data = [
			    'chat_id' => '@StockPrice_DOWN',
			    'text' => $Message,
			];
			$response = file_get_contents("https://api.telegram.org/bot$apiToken/sendMessage?" . http_build_query($data) );

			$sqlInsert=mysqli_query($conn, "UPDATE stock_condition SET isReport=1 WHERE StockSymbol='". $row["StockSymbol"] ."'");
			
			echo 'send success';
		}
		
	}

	



?>