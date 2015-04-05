Gallery = function()
{
};

Gallery.prototype.init = function(map, photoArray)
{
    var photo;
    
    while (photo = photoArray.pop())
    {
        photo.addTo(map);
    }
};

Gallery.prototype.test = function()
{
    //alert('foo');
};

Photo = function(id, latitude, longitude)
{
    this.id = id;
    this.latitude = latitude;
    this.longitude = longitude;
};

Photo.prototype.address = null;
Photo.prototype.latitude = null;
Photo.prototype.longitude = null;

Photo.prototype.getId = function()
{
    return this.id;
};

Photo.prototype.getLatitude = function()
{
    return this.latitude;
};

Photo.prototype.getLongitude = function()
{
    return this.longitude;
};

Photo.prototype.addTo = function(map)
{
    var locationIcon = L.icon({
        iconUrl: 'https://www.criticalmass.in/images/marker/marker-red.png',
        iconRetinaUrl: '/images/marker/marker-red-2x.png',
        iconSize: [25, 41],
        iconAnchor: [13, 41],
        popupAnchor: [0, -36],
        shadowUrl: '/images/marker/defaultshadow.png',
        shadowSize: [41, 41],
        shadowAnchor: [13, 41]
    });
    
    var photoMarker = L.marker([this.getLatitude(), this.getLongitude()], { icon:locationIcon, draggable: true });
    photoMarker.addTo(map);

    photoMarker.on('click', function()
    {
        var photoPath = '/photos/' + this.getId() + '.jpg';
        $.fancybox( {href : photoPath, title : 'Lorem lipsum'} );
    });
};