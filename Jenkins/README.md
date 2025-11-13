# Jenkins

## Using this repository

We use this GitHub project to store the Jenkinsfiles that will be executed from Jenkins. Each folder contains a single Jenkinsfile, and its name is self-explanatory — it identifies which job it refers to and what that job does.

## How does it work?

In Jenkins, Pipeline-type projects have a "Definition" section where you should select:
```
Pipeline script from SCM
```

The SCM type will be **Git**, and the repository URL should be:
```
https://github.com/Redsauce/RSM-Core/tree/develop/jenkins.git
```

The branch is */develop, and most importantly, to select which folder to use, you must specify it in the **"Script Path"** field. There you’ll enter the path to the Jenkinsfile, including its directory, for example:
```
Website Monitor pipeline/Jenkinsfile
```
