<template>
	<div class="sc-file-select">
		<div class="sc-file-select__side" v-loading="menuLoading">
			<div class="sc-file-select__side-menu">
				<el-tree ref="group" class="menu" :data="menu" :node-key="treeProps.key" :props="treeProps" :current-node-key="menu.length>0?menu[0][treeProps.key]:''" highlight-current @node-click="groupClick">
					<template #default="{ node }">
						<span class="el-tree-node__label">
							<el-icon class="icon"><el-icon-folder /></el-icon>{{node.label}}
						</span>
					</template>
				</el-tree>
			</div>
			<div class="sc-file-select__side-msg" v-if="multiple">
				已选择 <b>{{value.length}}</b> / <b>{{max}}</b> 项
			</div>
		</div>
		<div class="sc-file-select__files" v-loading="listLoading">
			<div class="sc-file-select__top">
				<div class="upload">
					<el-upload style="margin-right:10px" class="sc-file-select__upload" action="" multiple :show-file-list="false" :accept="accept" :on-change="uploadChange" :before-upload="uploadBefore" :on-progress="uploadProcess" :on-success="uploadSuccess" :on-error="uploadError" :http-request="uploadRequest">
						<el-button type="primary" v-auth="'system.attachment.create'" icon="el-icon-upload">本地上传</el-button>
					</el-upload>
				    <el-button type="info" @click="info" v-auth="'system.attachment.read'" icon="el-icon-info-filled">获取信息</el-button>
				    <el-button type="danger" @click="del" v-auth="'system.attachment.delete'" icon="el-icon-close">删除选中</el-button>
				</div>
				<div class="keyword">
					<el-input v-model="keyword" prefix-icon="el-icon-search" placeholder="文件搜索" clearable @keyup.enter="search" @clear="search"></el-input>
				</div>
			</div>
			<div class="sc-file-select__list" v-auth="'system.attachment.read'" >
				<el-scrollbar ref="scrollbar">
					<el-empty v-if="fileList.length==0 && data.length==0" description="无数据" :image-size="80"></el-empty>
					<div v-for="(file, index) in fileList" :key="index" class="sc-file-select__item">
						<div class="sc-file-select__item__file">
							<div class="sc-file-select__item__upload">
								<el-progress type="circle" :percentage="file.progress" :width="70"></el-progress>
							</div>
							<el-image :src="file.tempImg" fit="contain"></el-image>
						</div>
						<p>{{file.name}}</p>
					</div>
					<div v-for="item in data" :key="item[fileProps.key]" class="sc-file-select__item" :class="{active: value.includes(item[fileProps.url]) }" @click="select(item)">
						<div class="sc-file-select__item__file">
							<div class="sc-file-select__item__checkbox" v-if="multiple">
								<el-icon><el-icon-check /></el-icon>
							</div>
							<div class="sc-file-select__item__select" v-else>
								<el-icon><el-icon-check /></el-icon>
							</div>
							<div class="sc-file-select__item__box"></div>
							<el-image v-if="item[fileProps.url]" :src="getSrc(item[fileProps.url],item.extension)" fit="contain" lazy></el-image>
							<div v-else class="item-file item-file-doc">
								<i v-if="files[_getExt(item[fileProps.url])]" :class="files[_getExt(item[fileProps.url])].icon" :style="{color:files[_getExt(item[fileProps.url])].color}"></i>
								<i v-else class="sc-icon-file-list-fill" style="color: #999;"></i>
							</div>
						</div>
						<p :title="item[fileProps.fileName]">{{item[fileProps.fileName]}}</p>
					</div>
				</el-scrollbar>
			</div>
			<div class="sc-file-select__pagination">
				<el-pagination small background layout="prev, pager, next" :total="total" :page-size="pageSize" v-model:currentPage="currentPage" @current-change="reload"></el-pagination>
			</div>
			<div class="sc-file-select__do">
				<slot name="do"></slot>
				<el-button type="primary" v-if="get" :disabled="value.length<=0" @click="submit">确 定</el-button>
			</div>
		</div>
	</div>
</template>

<script>
	import config from "@/config/fileSelect"
	import filterBar from "@/config/filterBar"
	import table from "@/utils/table"

	export default {
		props: {
			modelValue: null,
			multiple: { type: Boolean, default: false },
			max: {type: Number, default: config.max},
			get: {type: Boolean, default:false},
			only: { type: Boolean, default: false },
			maxSize: {type: Number, default: config.maxSize},
		},
		data() {
			return {
				keyword: null,
				pageSize: 20,
				total: 0,
				currentPage: 1,
				data: [],
				menu: [],
				menuId: '',
				value: this.multiple ? [] : '',
                selects:[],
				fileList: [],
				accept: "",
                searchContent:"",
				listLoading: false,
				menuLoading: false,
				treeProps: config.menuProps,
				fileProps: config.fileProps,
				files: config.files
			}
		},
		watch: {
			multiple(){
				this.value = this.multiple ? [] : ''
				this.$emit('update:modelValue', JSON.parse(JSON.stringify(this.value)));
			}
		},
		mounted() {
			this.getMenu()
			this.getData()
		},
		methods: {
			getSrc(src,extension){
				if(extension == 'jpeg' || extension == 'gif' || extension == 'jpg' || extension == 'png' || extension == 'bmp' || extension == 'pic' || extension == 'svg'){
					return src;
				}else{
					return this.drawLogo(extension,'80px Arial');
				}
			},
			drawLogo: function(text, font) {
				// 创建画布
				let canvas = document.createElement('canvas');
				// 绘制文字环境
				let context = canvas.getContext('2d');
				// 设置字体
				context.font = font;
				// 获取字体宽度
				let width = context.measureText(text).width;
				// 如果宽度不够 240
				if (width < 240) {
					width = 240;
				} else {
					width = width + 30;
				}
				// 画布宽度
				canvas.width = width;
				// 画布高度
				canvas.height = width;
				// 填充白色
				context.fillStyle = '#ffffff';
				// 绘制文字之前填充白色
				context.fillRect(0, 0, canvas.width, canvas.height);
				// 设置字体
				context.font = font;
				// 设置水平对齐方式
				context.textAlign = 'center';
				// 设置垂直对齐方式
				context.textBaseline = 'middle';
				// 设置字体颜色
				context.fillStyle = '#000000';
				// 绘制文字（参数：要写的字，x坐标，y坐标）
				context.fillText(text, canvas.width / 2, canvas.height / 2);
				// 生成图片信息
				let dataUrl = canvas.toDataURL('image/png');
				return dataUrl;
			},
			//获取分类数据
			async getMenu(){
				this.menuLoading = true
				var res = await config.menuApiObj.get()
				this.menu = res.data
				this.menuLoading = false
			},
            info(){
                if(this.selects.length <= 0){
                    this.$message.warning('请选择文件');
                    return false;
                }
                for(const i in this.selects){
                    this.$alert("ID："+this.selects[i].id+"<br>文件名："+this.selects[i].filename+"<br>存储路径："+this.selects[i].path+"<br>地址："+this.selects[i].url+"<br>上传者："+(this.selects[i].admin_id ? "管理员ID-"+this.selects[i].admin_id : "用户ID-"+this.selects[i].user_id)+"<br>文件类型："+this.selects[i].mediatype+"<br>文件后缀："+this.selects[i].extension+"<br>文件大小："+this.selects[i].size+"<br>上传时间："+table.datetime(1,1,this.selects[i].create_time)+"<br>更新时间："+table.datetime(1,1,this.selects[i].update_time),'文件信息',{
                        confirmButtonText: '确定',
						dangerouslyUseHTMLString:true,
						customClass:'msgBox'
                    })
                }
            },
            del(){
                if(this.selects.length <= 0){
                    this.$message.warning('请选择文件');
                    return false;
                }
                var ids = [];
                for(const i in this.selects){
                    ids.push(this.selects[i].id)
                }
                this.$API.system.attachment.delete.post({ids:ids}).then(res=>{
                    if(res.code){
                        this.selects = [];
                        this.value = this.multiple ? [] : '';
                        this.$message.success(res.message);
                        this.getData();
                    }else{
                        this.$message.error(res.message);
                    }
                })
            },
			//获取列表数据
			async getData(){
				this.listLoading = true
				var reqData = {
					[config.request.page]: this.currentPage,
					[config.request.pageSize]: this.pageSize,
					[config.request.keyword]: this.keyword
				}
                var filter = {};
                if(this.menuId && this.menuId != '全部'){
                    filter.parent = this.menuId+filterBar.separator+"=";
                }
				if(this.only){
					filter.mediatype = this.only+filterBar.separator+"between";
				}
                if(this.searchContent){
                    reqData['search'] = this.searchContent
                }
                reqData['filter'] = JSON.stringify(filter)
				var res = await config.listApiObj.get(reqData)
				var parseData = config.listParseData(res)
				this.data = parseData.rows
				this.total = parseData.total
				this.listLoading = false
				this.$refs.scrollbar.setScrollTop(0)
			},
			//树点击事件
			groupClick(data){
				this.menuId = data.label
				this.currentPage = 1
				this.keyword = null
				this.getData()
			},
			//分页刷新表格
			reload(){
				this.getData()
			},
			search(){
                this.searchContent = this.keyword;
				this.currentPage = 1
				this.getData()
			},
			select(item){
                if(this.selects[item.id]){
                    delete(this.selects[item.id])
                }else{
                    this.selects[item.id] = item
                }
				const itemUrl = item[this.fileProps.url]
				if(this.multiple){
					if(this.value.includes(itemUrl)){
						this.value.splice(this.value.findIndex(f => f == itemUrl), 1)
					}else{
						this.value.push(itemUrl)
					}
				}else{
					if(this.value.includes(itemUrl)){
						this.value = ''
					}else{
						this.value = itemUrl
					}
				}
			},
			submit(){
				const value = JSON.parse(JSON.stringify(this.value))
				this.$emit('update:modelValue', value);
				this.$emit('submit', value);
			},
			//上传处理
			uploadChange(file, fileList){
				file.tempImg = URL.createObjectURL(file.raw);
				this.fileList = fileList
			},
			uploadBefore(file){
				const maxSize = file.size / 1024 / 1024 < this.maxSize;
				if (!maxSize) {
					this.$message.warning(`上传文件大小不能超过 ${this.maxSize}MB!`);
					return false;
				}
			},
			uploadRequest(param){
				var apiObj = config.apiObj;
				const data = new FormData();
				data.append("file", param.file);
				apiObj.post(data, {
					onUploadProgress: e => {
						param.onProgress(e)
					}
				}).then(res => {
					param.onSuccess(res)
				}).catch(err => {
					param.onError(err)
				})
			},
			uploadProcess(event, file){
				file.progress = Number((event.loaded / event.total * 100).toFixed(2))
			},
			uploadSuccess(res, file){
                this.fileList.splice(this.fileList.findIndex(f => f.uid == file.uid), 1)
                if(res.code !== 200){
                    this.$message.error(res.message);
                    return false;
                }
				var response = config.uploadParseData(res);
                this.getData();
				if(!this.multiple){
					this.value = response.url
				}
			},
			uploadError(err){
				this.$notify.error({
					title: '上传文件错误',
					message: err
				})
			},
			//内置函数
			_isImg(fileUrl){
				const imgExt = ['.jpg', '.jpeg', '.png', '.gif', '.bmp']
				const fileExt = fileUrl.substring(fileUrl.lastIndexOf("."))
				return imgExt.indexOf(fileExt) != -1
			},
			_getExt(fileUrl){
				return fileUrl.substring(fileUrl.lastIndexOf(".") + 1)
			}
		}
	}
</script>
<style scoped>
	.sc-file-select {display: flex;}
	.sc-file-select__files {flex: 1;}

	.sc-file-select__list {height:400px;}
	.sc-file-select__item {display: inline-block;float: left;margin:0 15px 25px 0;width:110px;cursor: pointer;}
	.sc-file-select__item__file {width:110px;height:110px;position: relative;}
	.sc-file-select__item__file .el-image {width:110px;height:110px;}
	.sc-file-select__item__box {position: absolute;top:0;right:0;bottom:0;left:0;border: 2px solid var(--el-color-success);z-index: 1;display: none;}
	.sc-file-select__item__box::before {content: '';position: absolute;top:0;right:0;bottom:0;left:0;background: var(--el-color-success);opacity: 0.2;display: none;}
	.sc-file-select__item:hover .sc-file-select__item__box {display: block;}
	.sc-file-select__item.active .sc-file-select__item__box {display: block;}
	.sc-file-select__item.active .sc-file-select__item__box::before {display: block;}
	.sc-file-select__item p {margin-top: 10px;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;-webkit-text-overflow:ellipsis;text-align: center;}
	.sc-file-select__item__checkbox {position: absolute;width: 20px;height: 20px;top:7px;right:7px;z-index: 2;background: rgba(0,0,0,0.2);border: 1px solid #fff;display: flex;flex-direction: column;align-items: center;justify-content: center;}
	.sc-file-select__item__checkbox i {font-size: 14px;color: #fff;font-weight: bold;display: none;}
	.sc-file-select__item__select {position: absolute;width: 20px;height: 20px;top:0px;right:0px;z-index: 2;background: var(--el-color-success);display: none;flex-direction: column;align-items: center;justify-content: center;}
	.sc-file-select__item__select i {font-size: 14px;color: #fff;font-weight: bold;}
	.sc-file-select__item.active .sc-file-select__item__checkbox {background: var(--el-color-success);}
	.sc-file-select__item.active .sc-file-select__item__checkbox i {display: block;}
	.sc-file-select__item.active .sc-file-select__item__select {display: flex;}
	.sc-file-select__item__file .item-file {width:110px;height:110px;display: flex;flex-direction: column;align-items: center;justify-content: center;}
	.sc-file-select__item__file .item-file i {font-size: 40px;}
	.sc-file-select__item__file .item-file.item-file-doc {color: #409eff;}

	.sc-file-select__item__upload {position: absolute;top:0;right:0;bottom:0;left:0;z-index: 1;background: rgba(255,255,255,0.7);display: flex;flex-direction: column;align-items: center;justify-content: center;}

	.sc-file-select__side {width: 200px;margin-right: 15px;border-right: 1px solid rgba(128,128,128,0.2);display: flex;flex-flow: column;}
	.sc-file-select__side-menu {flex: 1;}
	.sc-file-select__side-msg {height:32px;line-height: 32px;}

	.sc-file-select__top {margin-bottom: 15px;display: flex;justify-content: space-between;}
	.sc-file-select__upload {display: inline-block;}
	.sc-file-select__top .tips {font-size: 12px;margin-left: 10px;color: #999;}
	.sc-file-select__top .tips i {font-size: 14px;margin-right: 5px;position: relative;bottom: -0.125em;}
	.sc-file-select__pagination {margin:15px 0;}

	.sc-file-select__do {text-align: right;}
	
</style>
