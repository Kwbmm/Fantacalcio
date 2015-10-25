#TODO

##Backend

  + Correct lay_formation: not taking into account the fact the previous formations may have players that user sold or players not anymore in this championship.
  + Switch queries from `mysqli_real_escape` to parametrized queries.
     
     See http://wiki.hashphp.org/PDO_Tutorial_for_MySQL_Developers and http://stackoverflow.com/questions/60174/how-can-i-prevent-sql-injection-in-php
       
  + Fix insertion in DB: use only parametrized queries to add user input to the DB and for output use only htmlenetities (possibly through Twig).
  + Implement Log System (Monolog plugin maybe?)
  + Review ´utilities/generate.php`: split for modularity, check correctness
  + Add profile views
  + Implement "buy-session" mechanism to avoid duplicate POST requests

##Frontend

  + Allow ordering players in buy page with respect to cost, name, role
  + Add a footer to display quick info on how many players are missing in the roster
  + In desktop mode display roster page with multiple tables: one per role.