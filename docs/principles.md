# Principles

Strata Data offers standardised ways to get data from external sources, along with some convenience functions to make 
working with data easier. The basic principles of how it works is explained here.

## Architecture



![Architecture of Strata Data](/Users/sjones/Sites/strata/data/docs/img/architecture.png)

Strata Data reads data from a **Data source**. This could be a REST API or local filesystem.

Data has a particular **data format**, for example, JSON. This is applied as a filter when data is read in. As data is read in, you can add additional **filters** to transform the data. 

Data can then be tranformed into an object via **mapping**, or returned as a plain array.

Returned data is stored either as one **item** or a **collection**. 

Each item contains the **data** itself, in array or object format (if mapping has been used) and **metadata**, which is data about the data. 

## Data sources

## Data format

## Filters

## Mapping





