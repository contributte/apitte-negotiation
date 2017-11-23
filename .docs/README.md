# Apitte/Negotiation

## Content

- [Installation - how to register a plugin](#plugin)
- [Configuration - how to configure](#configuration)
- [Negotiation - content negotiation](#negotiation)
- [Playground - real examples](#playground)

## Plugin

This plugin requires [Apitte/Core](https://github.com/apitte/core) library.

At first you have to register the main extension.

```yaml
extensions:
    api: Apitte\Core\DI\ApiExtension
```

Secondly, add the `NegotiationPlugin` plugin.

```yaml
api:
    plugins:
        Apitte\Negotiation\DI\NegotiationPlugin:
```

## Configuration

You can configure a few options.

```
api:
    plugins: 
        Apitte\Negotiation\DI\NegotiationPlugin:
            unification: false
            catchException: false
```

- `unification` - Change default `JsonTransformer` to `JsonUnifyTransform`. You will see in next capter.
- `catchException` - Great for front-end developers, convert (catch) Tracy-exception to JSON. 

## Negotiation

This plugin adds new features. They are called decorators, negotiators and transformers.

Basically, decorator listen on specic event and can modify incomming request or outgoing response directly or via negotiator.
The negotiator has the handling logic, if request has an appropriate extension or header and call the transformer.
All modifications (trasforming data from A to B) is done in transformer, e.q. transform array to json.    

### Decorators

There are 2 predefined decorators:

- `ResponseEntityDecorator` - It listens on 2 events, after dispatching and after exception. It means this decorator is trigged when the response is returned from controller 
or if some exception is throwed.

- `ThrowExceptionDecorator` - This decorator is appliable only in debug mode. It just diplays the exception for you.

### Negotiators

The main goal of the negotiator is determine if given request/response apply all conditions and call the transformer.

There are 3 predefined transformers:

- `SuffixNegotiator` - It's called if the URI ends with the given suffix, e.q `example.com/users.json` -> `json`.

- `DefaultNegotiator` - It's called when the route is matched and annotation `@Negotiation(default = true)` has provided default attribute. 

- `FallbackNegotiator` - The last one transformer. It's called when no other negotiator is matched.

### Transformers

These classes transform data formats from A to B.

- `JsonTransform` - The most used transformer, `entity` -> `json`.

- `JsonUnifyTransform` - Applied when the `unification` is enabled. Transformer, `entity` -> `json` (with formatted response).

- `CsvTransform` - Transform `entity` -> `csv`, but the entity must have an appropriate data.

- `RendererTransformer` - This is special transformer. If annotation `@Negotiation(renderer = App\Some\Class` has provided renderer attribute, 
this transformer recieve that renderer, `entity` -> `renderer(entity)`.

## Playground

I've made a repository with full applications for education.

Take a look: https://github.com/apitte/playground
