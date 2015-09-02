#TODO

  + Restyling of `modulo.twig`: right now on small devices is not the best user experience
  + Switch queries from `mysqli_real_escape` to parametrized queries.
  + Fix insertion in DB: use only either `mysqli_real_escape` or parametrized queries to add user input to the DB and for output use only htmlenetities (possibly through Twig).
