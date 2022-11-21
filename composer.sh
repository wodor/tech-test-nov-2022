#! /bin/bash
docker-compose exec --workdir=/app web composer $1 $2 $3 $4
