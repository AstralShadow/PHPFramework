# Routers
- [ ] Simple routers
- [ ] Modular routers
    - Lazy loading for routers
> Simple Routers (the current ones) and Modular routers, providing lazy loading for routing, effectively creating a way to have different routers for subsystems without loading all of them, as routers might as well be used for managing permissions

- [ ] Provide an uniform variable access method that handles CREATE, POST, PUT, DELETE and other methods
    - Possibly using php://input
    - <https://stackoverflow.com/questions/27941207/http-protocols-put-and-delete-and-their-usage-in-php>
    - <https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/DELETE>

- [ ] Permit multiple handlers per path with different priority.
    > Higher priority handlers can be used to sort out permissions.


# Database

- [ ] Let the find method query only id's and later fetch/load from cache the objects
    > This is noted in the wiki, so remove it once fixed.

- [ ] Provide a few scripts to automatically apply migrations.
    > Stated in wiki, remove it once done.

- [ ] Plan an initialization method to be called when fetching an entry from database.
    > Currently the constructor handles data creation, so we need another way to provide the user with a way to set a valid state for their objects when they are being fetched.

# Templates
- [x] Rewrite the template system in a manner where it handles commands as tokens to allow user input to be inserted with different method that would prevent it being used inside parsed commands.
- [x] Fix resource path root not being passed in `NodeTag.php`

- [ ] Add ${set:var=value} command to est default value for variables.
    - The purpose of this is to have an included template file modify variables in it's outer file.
    - Doing that will require delayed parsing of variable fields with missing values or parsing includes first. The delayed parsing is a better approach as it will permit some templates to affect other templates' inclusion.


# Wiki/Tutorials
- [ ] Explore the possibility of having models that point at the same table and represent a different "view" on it.


# Management
- [ ] Also create a few scripts to notify of breaking changes in the framework/API
    > May not be necessary considering that only i use this.
- [ ] Add a warning that the API is a subject to change.
 
- [ ] Provide a mechanist for devs to set template and resource dir.
    > Currently exposing them as static variables, perhaps i should make a configuration file.


# Misc

- [ ] Learn and use something that would prevent data races.

