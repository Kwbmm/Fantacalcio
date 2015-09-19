#TODO

  1. ~~Split routes in their own single files~~
  2. Restyling of `modulo.twig`: right now on small devices is not the best user experience
  3. Switch queries from `mysqli_real_escape` to parametrized queries.
  4. Fix insertion in DB: use only parametrized queries to add user input to the DB and for output use only htmlenetities (possibly through Twig).