# Classroom Attendance Service

## Assumptions
There are 10 million students in UK. 
Is it possible to build a system that is able to handle classroom attendandance for all of them?
I assumed that the nature of attendance checking is that it happens at a certain time of the day 
throughout the country.

It is quite easy to write a system that saves a single row in less than 300 ms, once per second or so. 
At first glance, more time is spent on dispatching request within the server and initialising php and framework 
than on writing the data to data store. 
If there are more requests, we can scale horizontally and add more instances 
of the application to process the requests.
This will make things better, until the underlying data store is saturated. 
RDBMS will not behave well if there are concurrent writes into the same indexed table.

I also assumed that absence is checked per lesson, not per day. This is how it worked where I was brought up, if this
assumption is wrong, read "day" wherever I write "lesson". 


## Mechanics 
I tried to build a solution that wouldn't be held back by a single sql database instance when
it comes to submitting the attendance.
The rest of the system is happily using RDBMS and enjoying its simplicity and robustness.

The mechanism relies on two actions that can be performed by a teacher. 
 - reporting absence
 - completing the lesson (stating tha all other students are present)

Inserting a lot of records into a single, indexed sql db table in a short time would result in a bottleneck, 
that is why I decided to use an alternative data storage approach. 

This service is using MongoDB. On absence report, it is upserting the root document, 
which is representing a single lesson - all absences are stored as embedded documents. 
The idea is that a single teacher would do consecutive absence reports for a lesson, 
It will result in storing data in the same document, it would be updated couple of times, but rarely at the same time by others. 
On the other hand we can expect multiple lessons to be updated at the same time by many teachers, but this will be writes
to respective documents, this should be handled well by MongoDB. 

The absence recording mechanism is utilising a radical approach, it does not verify any information with the sql DB. 
A compromise solution would be to pre-warm a cache of existing lessons, groups and students in Redis or Memcache 
and verify according to this information.
Eventual consistency is achieved on complete lesson request, where all the data can be verified, modified and enriched,
before it becomes a source of truth for students' attendance. 

This solution is clearly optimised for writes, but all this data is stored for a reason. 
I assumed that attendance of a group is important, as well as attendance of single student. 
I used MongoDB aggregation mechanism, the results can be shown here in http://localhost/stats. 
This endpoint provides a json of attendance at the group level. The same approach can be used to get stats on school level, etc.
http://localhost/stats.

I've also realised that, with smart approach to reporting, only absences need to be recorded. 
The rest of the students are assumed present when the lesson is completed.
I guess that this saves about 80% of writes, and allows for smaller infrastructure bills. 
Interesting side effect is that this makes the system vulnerable to flu outbreaks or a pandemic, when scaling up could be required.

## Code
Whole application is implemented using symfony framework, with its ORM and ODM. 
From the code design perspective, not much to write home about. 
There is a single service `\App\Attendance\AbsenceService` that orchestrates the an aenemic model 
to write data provided to relevant stores. 
All necessary functionality is encapsulated in the service, the `\App\Tests\IntegrationTest` proves that
application works without http controllers.

## Running 
The application can be run with
```
docker-compose up -d
composer install
```

### Db setup and seeding 
All commands have to run from within container, assuming that web docker container is running. 
```
docker-compose exec --workdir=/app web bin/console do:mi:mi
docker-compose exec --workdir=/app web bin/console do:fi:lo
```
Visit http://localhost to manually record an attendance in a lesson. 

### Running tests
```
docker-compose exec --workdir=/app web bin/phpunit
```

This will run an integration test that runs on the same DB as dev. 
Thanks to this some stats become available at http://localhost/stats


### Docker on mac and windows
To achieve sensible performance it is better to use shared volume for vendors folder. 
Uncomment `- vendor:/app/vendor` line in `docker-compose.yaml` to enable it.
To install deps within the container run 
```
./composer.sh install 
```


## Aspects

### 300ms threshold.
I have achieved under 300ms response times (typically around 180ms) on docker for mac platform, 
in dev mode but with acpu cache enabled.
This means that on a linux machine we will be on the safe side of response time. 
I'm happy to discuss how 99.99% responses under 300ms can be provided. 

### 99.99% SLO 
This requirement means that application can be unavailable for less then 52 minutes per year, or 8.6s per day. 
To achieve this with any confidence more than one instance must be running at the same time.
An application release model similar to blue-green deployment will be required to avoid downtimes during releases.
Even if we assume that application doesn't break we need to take into account how it is run.
We need a load balancer, RDBMS and a Mongodb instance working at the same time. 
MongoDB atlas has an SLA of 99.995% (https://www.mongodb.com/cloud/atlas/reliability), 
Amazon Elastic Load Balancing has SLA of 99.99%
AWS Aurora has SLA 99.99% 
The composite SLA for the whole application will be 99.975%.
In fact, absence endpoint does not require RDBMS, its calculated SLA would be 99.985% which is closer to the goal. 



### Testability

Unit tests exist for the core service.  
There is also an integration test which is more of a demonstration of how the service works, but it asserts if 
records were saved to the databases.

### Simplicity

The idea is simple - write what needs to be written frequently to a separate storage designed for it, keep the rest
traditional and transactional. Implementation is somewhat more involved than I anticipated. Symfony helps a lot, but 
odm bundle seems to stay behind newest advances in Mongo. Some direct queries had to be written. 

### Security

I didn’t implement full authentication mechanism, but this design allows to provide one easily.
To secure the attendance endpoint I would use JWT, I've used `lcobucci/jwt` before with great results.
Using this type of token would allow to authenticate the request without calling any other service or database,
just by using the key to verify if the token is valid. 
The `completeLesson` call could be verified with the token issuer, to check if the user didn't log out earlier or have 
been invalidated in any other way. 

### Observability

Errors are logged and are available as stdout in the container. This stream can be taken further to central logging system.
It would be great to have Prometheus utilised within the application for example to monitor closely how many absence records are 
recorded per second. 
