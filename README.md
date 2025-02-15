# BadBotBundle

If you visit a link likely to be a bot probing the site for weakness, you'll be banned!

## How it works

* At the beginning of every request, check if the IP is marked as banned and block it if is.  
* At the end of every request that returns a 404, if the path is a likely bad bot path, add the IP to the banned list.
* The banned IP list can either be in the application cache (fast and will reset after a configurable time) or persisted in a key value list.

The key/value bundle stores the following:

* probe_paths: e.g. phpinfo, wp-admin
* banned_ip: (optional) list of banned IPs (if we want to persist them)

Idea: BadBotServer, to track banned IPs, probe paths, user-agents, etc.


$kvManager->add('banned_ips', $ip);
OR
```php
$ip = $cache->get('banned_ips_' . $ip), function (ItemCache) {
    // set the timeout
}


In BeforeRequestListener:

Check the banned IP list (either via the cache)

```php
if ($cache->has('banned_ips_' . $ip)) {
    // abort the request
}
```

Inspired by  lsbproject/blacklist-bundle https://github.com/AntoineLemaire/BlacklistBundle

Installation
============

```console
composer require survos/bad-bot-bundle
```

### Update database schema

```console
bin/console doctrine:schema:update --force
bin/console bot:populate https://github.com/mitchellkrogza/apache-ultimate-bad-bot-blocker/raw/refs/heads/master/_generator_lists/bad-ip-addresses.list
```

Usage
=====

@todo: https://github.com/mitchellkrogza/apache-ultimate-bad-bot-blocker


@todo: refactor with annotations.

Really this part could be a generic KeyValuesBundle, e.g. $kvManager->list('ip')

```php
    use Survos\BadBotBundle\Validator\Constraints\IsNotBlacklisted;

    //...

    /**
     * 'baz' type isn't defined in bundle, so it will be handled with
     * default_type class. Default one has no validation and will compare
     * any value with other existed
     *
     * @IsNotBlacklisted(type="baz", caseSensetive=true)
     * @var string
     */
    private $bar;

    /**
     * 'email' type will dissallow to put invalid emails in blacklist
     *
     * @IsNotBlacklisted(type="email", caseSensetive=true)
     * @var string
     */
    private $email;
```

Types
-----

Bundle tries to validate exact blacklist type with validator types.
You can implement your own type or use default one.
To add your own validator just implement `TypeInterface`

e.g.

```php
use LSBProject\BlacklistBundle\Type\TypeInterface;
use LSBProject\BlacklistBundle\Type\DefaultType;

class EmailType extends DefaultType implements TypeInterface
{
    /**
     * {@inheritDoc}
     */
    public function satisfies(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $type): bool
    {
        return $type === 'email';
    }
}
```

and tag it with `lsbproject.blacklist.type`

```yaml
  email_blacklist_type:
    class: 'LSBProject\BlacklistBundle\Type\EmailType'
    tags:
      - { name: 'lsbproject.blacklist.type' }
```

Default
-------

If there are no supported types found bundle will use default type.
You can override it in config:

```yaml
    lsb_project_blacklist:
      default_type: LSBProject\BlacklistBundle\Type\DefaultType
```

Validate storage
----------------

If you do not want to use database as a storage for blacklist you
can implement your own `validate` method for a separate or default types.

example of default `validate`

```php
class DefaultType implements TypeInterface
{
    //...    

    /**
     * {@inheritDoc}
     */
    public function validate(
        string $value,
        Constraint $constraint,
        ExecutionContextInterface &$context,
        BlacklistManagerInterface $manager
    ): void {
        if ($manager->isBlacklisted($value, $constraint->type, $constraint->caseSensetive)) {
            $context->buildViolation($constraint->message)->addViolation();
        }
    }
}
```
