#TODO

  1. ~~Split routes in their own single files~~
  2. ~~Restyling of `modulo.twig`: right now on small devices is not the best user experience~~
  3. Switch queries from `mysqli_real_escape` to parametrized queries.
     
     See http://wiki.hashphp.org/PDO_Tutorial_for_MySQL_Developers and http://stackoverflow.com/questions/60174/how-can-i-prevent-sql-injection-in-php
       
  4. Fix insertion in DB: use only parametrized queries to add user input to the DB and for output use only htmlenetities (possibly through Twig).
  5. Implement Log System (Monolog plugin maybe?)
  6. Review ´utilities/generate.php`: split for modularity, check correctness