Pull the project.

- `composer install`.
- Edit the .env.example to be .env.
- Configure your DB settings.
- Run `php artisan migrate`.

if you wish to populate the database with dummy data:
- `php artisan db:seed`

Call `/api/add-sub` with the parameters `msisdn`, and `product_id` to insert a
subscription into the system. When inserting the subscription, and if it is not
an Alias, then system will also get the Alias in an attempt to reverse engineer
the subscriptions from the Alias.

Call `/api/search` with any of the following parameters:
- `msisdn` this can also be an alias.
- `product_id`
- `start_date`
- `end_date`

Features I didn't implement:
- mx decrption. This was a simple matter of time however the logic is very
similar to that of getting the Alias. If the first character is a D or X then
call the API to decrypt it. If the return is an Alias then get the original
msisdn.

- Support 4 different returned data types. My intention was to use Laravel's
reponse object to return these types, however I didn't realise Laravel didn't
support them and thus I'd have to write my own functions. I would have built
these using the array objects that come back but I simply didn't have the time.

- Changing the return type based on the Accept header. This is relatively
simple. By using `$request->header('accept')` I can find out what parameter is
set, however because the above multiple return types wasn't done this wasn't
added in either as a `switch` statement with nothing to do seemed very
pointless.
