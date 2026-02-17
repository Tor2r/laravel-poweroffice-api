The PowerOffice Api returs the following response codes:

200 Given when resource returned / updated / deleted successfully
201 Given when resource was successfully created.
204 Given when there is no content to return (response body is empty)
400 Given when request is badly formatted
401 Given when request is unauthorized (Access Token is missing or invalid)
403 Given when request is forbidden (Integration does not have required permission to use endpoint)
404 Given when resource was not found (ex: customer not found, not that the url is not found)
409 Given when resource is in use and cannot be deleted
429 Given when request is throttled (too many requests)
default When request is not processed correctly a ProblemDetail object is returned

API Swagger,
Products: https://prdm0go0stor0apiv20eurw.z6.web.core.windows.net/?urls.primaryName=Products%20and%20Product%20Groups#/Products/GetProductById

API Swagger, Customers: https://prdm0go0stor0apiv20eurw.z6.web.core.windows.net/?urls.primaryName=Customers#/

API Swagger, Sales Orders: https://prdm0go0stor0apiv20eurw.z6.web.core.windows.net/?urls.primaryName=Sales%20Orders

