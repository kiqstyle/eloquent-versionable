# Versioning behaviour for Eloquent models

Careful: Eloquent first method on versioned models will get the first result that doesn't has 
next value, meaning if you update the first register it will set a date to next column and it 
will find the next register with next column with value null, that could be another id 

# For contributors
* INIT
  * RUN: 'make up'
  * RUN: 'make composer-install'
* TESTS
  * RUN: 'make test'