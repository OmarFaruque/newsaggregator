## Docker Setup for News Aggregator API

This project uses Docker to provide a consistent development environment. Below are the steps to set up and run the application using Docker.

### Prerequisites

Ensure that you have the following installed on your local machine:
- [Docker](https://www.docker.com/get-started)
- [Docker Compose](https://docs.docker.com/compose/install/)

### Docker Setup

The project includes a `docker-compose.yml` file that defines the services required to run the application. These services include the web application (Laravel) and a MySQL database.

### Running the Application

1. **Clone the repository** (if you haven’t already):

   ```bash
   git clone https://github.com/OmarFaruque/newsaggregator.git
   cd newsaggregator

2. **Build the Docker containers** :
Run the following command to build the containers based on the `Dockerfile` and `docker-compose.yml`:
   
   ```bash
   docker-compose build

3. **Start the Docker containers** :
Once the build is complete, start the application and database containers
   
   ```bash
   docker-compose up -d

4. **Access the Application** :
The Laravel application should now be running on `http://localhost:8000`
